<?php

namespace DrupalCodeBuilder\Generator\Collection;

use DrupalCodeBuilder\Generator\BaseGenerator;

/**
 * The collection of components for a generate request.
 *
 * This holds the instantiated components, in several different structures:
 * - The linear list of components, which this class can iterate over.
 * - The tree of components arranged by request, that is, where the components
 *   are arranged according to which component requested which.
 * - The tree of components arranged by containment, that is, where components
 *   are arranged according to which component contains which. This is only
 *   available once assembleContainmentTree() has been called. After this, no
 *   more components may be added.
 * - The list of local names which each component uses when it spawns further
 *   components.
 */
class ComponentCollection implements \IteratorAggregate {

  /**
   * The list of instantiated components.
   *
   * These are iterated over by this class.
   *
   * @var \DrupalCodeBuilder\Generator\BaseGenerator[]
   */
  private $components = [];

  /**
   * The ID of the root generator.
   *
   * @var string
   */
  private $rootGeneratorId;

  /**
   * The requesters tree.
   *
   * A tree where each key is a component ID, and each value is the ID of the
   * component that first requested it.
   *
   * @var array
   */
  private $requesters = [];

  /**
   * The IDs of components which are roots.
   *
   * An array whose keys are IDs and values are TRUE.
   *
   * @var array
   */
  private $roots = [];

  /**
   * List of each component's closest requesting root.
   *
   * An array whose keys are IDs and values are the closest requesting root ID.
   *
   * @var arrray
   */
  private $requestRoots = [];

  /**
   * The list of local names.
   *
   * An array whose keys are component unique IDs. Each item is itself an array
   * whose keys are local names, and whose values are the unique ID of that
   * component.
   *
   * @var array
   */
  private $localNames = [];

  /**
   * The containment tree.
   *
   * A tree of parentage data for components, as an array keyed by the parent
   * component name, where each value is an array of the names of the child
   * components. So for example, the list of children of component 'foo' is
   * given by $tree['foo'].
   *
   * @var array
   */
  private $tree = NULL;

  /**
   * Indicates the the collection is locked and no more components may be added.
   *
   * @var bool
   */
  private $locked = FALSE;

  /**
   * Returns the iterator for this object.
   */
  public function getIterator() {
    return new \ArrayIterator($this->components);
  }

  /**
   * Adds a component to the collection.
   *
   * @param $local_name
   *   The local name for the component, that is, the name used within the
   *   requesting components list of components to spawn, whether from
   *   properties or from requests.
   * @param $component
   *   The component to add.
   * @param $requesting_component
   *   The component that requested the component being added. May be NULL if
   *   the component being added is the root component.
   */
  public function addComponent($local_name, BaseGenerator $component, $requesting_component) {
    // Components may not be added once the collection is locked.
    if ($this->locked) {
      throw new \LogicException("Attempt to add component to locked collection.");
    }

    $key = $component->getUniqueID();

    if (isset($this->components[$key])) {
      throw new \Exception("Unique ID $key already in use.");
    }

    // If this is the first component, it's the root.
    if (!isset($this->rootGeneratorId)) {
      $this->rootGeneratorId = $key;
    }

    // If this is *a* root, keep track of it.
    if ($component instanceof \DrupalCodeBuilder\Generator\RootComponent) {
      $this->roots[$key] = TRUE;
    }

    if ($requesting_component) {
      // Add to the array of requesters.
      if (!isset($this->requesters[$key])) {
        // TODO: store multiple requesters?
        $this->requesters[$key] = $requesting_component->getUniqueID();
      }

      // Keep track of the nearest requesting root component.
      $this->requestRoots[$key] = $this->determineNearestRequestingRoot($key);

      // Add to the array of local names.
      $this->localNames[$requesting_component->getUniqueID()][$local_name] = $key;
    }

    $this->components[$key] = $component;
  }

  /**
   * Determines the closest requester that is a root component.
   *
   * @param $key
   *   The ID of the component.
   *
   * @return
   *   The ID of the closest component in the request chain that is a root
   *   component.
   */
  private function determineNearestRequestingRoot($key) {
    // We're ascending a tree whose root is a root component, so we have to
    // find a root eventually.
    // Note that the nearest requesting root of a root component is not itself.
    // E.g. with this chain:
    //   root -> requested -> inner_root
    // the nearest root of inner_root is root.
    do {
      $key = $this->requesters[$key];
    }
    while (!isset($this->roots[$key]));

    return $key;
  }

  /**
   * Gets the root component ID.
   *
   * @return
   *   The root component ID.
   */
  public function getRootComponentId() {
    return $this->rootGeneratorId;
  }

  /**
   * Returns whether the collection has a component with the given ID.
   *
   * @param string $id
   *   The component unique ID.
   *
   * @return bool
   *   Whether the collection has a component with this ID.
   */
  public function hasComponent($id) {
    return isset($this->components[$id]);
  }

  /**
   * Gets all components.
   *
   * @return array
   *   The array of components.
   */
  public function getComponents() {
    return $this->components;
  }

  /**
   * Gets the root component.
   *
   * @return
   *   The root component.
   */
  public function getRootComponent() {
    return $this->components[$this->rootGeneratorId];
  }

  /**
   * Returns the component with the given ID.
   *
   * @param string $id
   *   The component unique ID.
   *
   * @return
   *   The component.
   */
  public function getComponent($id) {
    return $this->components[$id];
  }

  /**
   * Assemble a tree of components, grouped by what they contain.
   *
   * For example, a code file contains its functions; a form component
   * contains the handler functions.
   *
   * This iterates over the flat list of components assembled by
   * ComponentCollector, and re-assembles it as a tree.
   *
   * The tree is an array of parentage data, where keys are the names of
   * components that are parents, and values are flat arrays of component names.
   * The top level of the tree is the root component, whose name is its type,
   * e.g. 'module'.
   * To traverse the tree:
   *  - access the base component name
   *  - iterate over its children
   *  - recursively do the same thing to each child component.
   *
   * Not all components in the component list need to place themselves into the
   * tree, but this means that they will not participate in file assembly.
   *
   * @return
   *  A tree of parentage data for components, as an array keyed by the parent
   *  component name, where each value is an array of the names of the child
   *  components. So for example, the list of children of component 'foo' is
   *  given by $tree['foo'].
   */
  public function assembleContainmentTree() {
    // Lock the collection.
    $this->locked = TRUE;

    $this->tree = [];
    foreach ($this->components as $id => $component) {
      $parent_name = $component->containingComponent();

      if (empty($parent_name)) {
        continue;
      }

      // Handle tokens.
      if ($parent_name == '%root') {
        $parent_name = $this->rootGeneratorId;
      }
      elseif ($parent_name == '%requester') {
        // TODO: consider whether this might go wrong when a component is
        // requested multiple times. Unlikely, as it tends to be containers
        // that are re-requested.
        $parent_name = $this->requesters[$id];
      }
      elseif (substr($parent_name, 0, strlen('%requester:')) == '%requester:') {
        $requester_id = $this->requesters[$id];

        $path_string = substr($parent_name, strlen('%requester:'));
        $path_pieces = explode(':', $path_string);

        $component_id = $requester_id;
        foreach ($path_pieces as $path_piece) {
          $local_name = $path_piece;

          assert(isset($this->localNames[$component_id][$local_name]), "Failed to get containing component for $id, local name $local_name not found for ID $component_id.");

          $component_id = $this->localNames[$component_id][$local_name];
        }

        $parent_name = $component_id;
      }
      elseif (substr($parent_name, 0, strlen('%sibling:')) == '%sibling:') {
        // TODO: remove this functionality, replace with '%requester:FOO'.
        $requester_id = $this->requesters[$id];
        $sibling_local_name = substr($parent_name, strlen('%sibling:'));

        assert(isset($this->localNames[$requester_id][$sibling_local_name]), "Failed to get containing component for $id, local name $sibling_local_name not found for ID $requester_id.");

        $parent_name = $this->localNames[$requester_id][$sibling_local_name];
      }

      assert(isset($this->components[$parent_name]), "Containing component '$parent_name' given by '$id' is not a component ID.");

      $this->tree[$parent_name][] = $id;
    }

    return $this->tree;
  }

  /**
   * Gets the containment tree.
   *
   * @return array
   *   The tree assembled by assembleContainmentTree().
   */
  public function getContainmentTree() {
    if (is_null($this->tree)) {
      throw new \LogicException("Tree has not yet been assembled.");
    }

    return $this->tree;
  }

  /**
   * Gets the IDs of a component's children in the tree.
   *
   * @param string $component_id
   *   The parent ID.
   *
   * @return string[]
   *   The child IDs.
   */
  public function getContainmentTreeChildrenIds($component_id) {
    $tree = $this->getContainmentTree();
    return $tree[$component_id] ?? [];
  }

  /**
   * Gets a component's children in the tree.
   *
   * @param BaseGenerator $component
   *   The component to get children for.
   *
   * @return BaseGenerator[]
   *   The child components, keyed by unique ID.
   */
  public function getContainmentTreeChildren(BaseGenerator $component) {
    $component_id = $component->getUniqueID();

    $tree = $this->getContainmentTree();

    $child_ids = $tree[$component_id] ?? [];
    $return = [];
    foreach ($child_ids as $id) {
      $return[$id] = $this->components[$id];
    }

    return $return;
  }

  /**
   * Returns the closest requester that is a root component.
   *
   * This may be called before the collection is complete.
   *
   * @param BaseGenerator $component
   *   The component to get children for.
   *
   * @return
   *   The root component.
   */
  public function getClosestRequestingRootComponent(BaseGenerator $component) {
    $component_id = $component->getUniqueID();

    $closest_requesting_root_id = $this->requestRoots[$component_id];
    return $this->components[$closest_requesting_root_id];
  }

}
