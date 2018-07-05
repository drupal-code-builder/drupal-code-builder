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
 * - The list of merge tags for components, grouped by closest requesting root,
 *   then type.
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
   * The list of request paths.
   *
   * An array whose keys are component unique IDs. Each item is a string
   * representing the path of local names to that component from the root, with
   * each name separated by '/'.
   *
   * @var array
   */
  private $requestPaths = [];

  /**
   * The containment tree.
   *
   * A tree of parentage data for components, as an array keyed by the parent
   * component name, where each value is an array of the names of the child
   * components. So for example, the list of children of component 'foo' is
   * given by $tree['foo'].
   *
   * Do not access this directly, but use getContainmentTree(), which checks
   * that the tree has been assembled and is available.
   *
   * @var array
   */
  private $tree = NULL;

  /**
   * The merge tags.
   *
   * An lookup array of component unique IDs, whose successive levels of nesting
   * are:
   *  - the ID of the closest root requester
   *  - the component type
   *  - the component merge tag
   */
  private $mergeTags = [];

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
   * Returns a unique key for a component.
   *
   * This is unique per component object, but does not depend on request data,
   * so it cannot be used to deduplicate different objects.
   *
   * @param BaseGenerator $component
   *   The component.
   *
   * @return string
   *   The unique key.
   */
  public function getComponentKey(BaseGenerator $component) {
    // TODO: Change this to the more succinct spl_object_id() once we drop
    // support for PHP < 7.2.
    return spl_object_hash($component);
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

    $key = $this->getComponentKey($component);

    if (isset($this->components[$key])) {
      throw new \Exception("Unique ID $key already in use, by component with request path {$this->requestPaths[$key]}.");
    }

    // If this is the first component, it's the root.
    if (!isset($this->rootGeneratorId)) {
      $this->rootGeneratorId = $key;

      $request_path = 'root';

      // Create the first item in the request paths array.
      $this->requestPaths[$key] = $request_path;
    }

    // If this is *a* root, keep track of it.
    if ($component instanceof \DrupalCodeBuilder\Generator\RootComponent) {
      $this->roots[$key] = TRUE;
    }

    if ($requesting_component) {
      $request_path = $this->requestPaths[$this->getComponentKey($requesting_component)] . '/' . $local_name;

      // Add to the array of request paths.
      $this->requestPaths[$key] = $request_path;

      // Add to the array of requesters.
      if (!isset($this->requesters[$key])) {
        // TODO: store multiple requesters?
        $this->requesters[$key] = $this->getComponentKey($requesting_component);
      }

      // Keep track of the nearest requesting root component.
      $closest_requesting_root = $this->determineNearestRequestingRoot($key);
      $this->requestRoots[$key] = $closest_requesting_root;

      // Add to the array of local names.
      $this->localNames[$this->getComponentKey($requesting_component)][$local_name] = $key;
    }

    // Add to the merge tags list.
    if ($merge_tag = $component->getMergeTag()) {
      // $closest_requesting_root will be set because the root component has
      // no merge tag.
      $this->mergeTags[$closest_requesting_root][$component->getType()][$component->getMergeTag()] = $key;
    }

    $this->components[$key] = $component;
  }

  /**
   * Add details for a component's that's been replaced by an existing one.
   *
   * This ensures that the local names array still has track of the requested
   * local name, even though the request didn't result in a new component.
   *
   * @param $local_name
   *   The local name for the component that is being discarded, that is, the
   *   name used within the requesting components list of components to spawn,
   *   whether from properties or from requests.
   * @param $existing_component
   *   The existing component which caused the new component to be discarded.
   * @param $requesting_component
   *   The component that requested the new component being discarded.
   */
  public function addAliasedComponent($local_name, BaseGenerator $existing_component, BaseGenerator $requesting_component) {
    $this->localNames[$this->getComponentKey($requesting_component)][$local_name] = $this->getComponentKey($existing_component);
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
   * Gets all components.
   *
   * @return array
   *   The array of components.
   */
  public function getComponents() {
    return $this->components;
  }

  /**
   * Gets the request paths.
   *
   * @return array
   *   An array whose keys are the internal IDs and values are the request
   *   paths.
   */
  public function getComponentRequestPaths() {
    return $this->requestPaths;
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
   *
   * @throws \Exception
   *  Throws an exception if the return value of containingComponent() for a
   *  component is unrecognized as representing another component.
   */
  public function assembleContainmentTree() {
    // Lock the collection.
    $this->locked = TRUE;

    $this->tree = [];
    foreach ($this->components as $id => $component) {
      $containing_component_token = $component->containingComponent();

      if (empty($containing_component_token)) {
        continue;
      }

      // Handle tokens.
      if ($containing_component_token == '%root') {
        $parent_name = $this->rootGeneratorId;
      }
      elseif ($containing_component_token == '%requester') {
        // TODO: consider whether this might go wrong when a component is
        // requested multiple times. Unlikely, as it tends to be containers
        // that are re-requested.
        $parent_name = $this->requesters[$id];
      }
      elseif (substr($containing_component_token, 0, strlen('%requester:')) == '%requester:') {
        $requester_id = $this->requesters[$id];

        $path_string = substr($containing_component_token, strlen('%requester:'));
        $path_pieces = explode(':', $path_string);

        $component_id = $requester_id;
        foreach ($path_pieces as $path_piece) {
          $local_name = $path_piece;

          assert(isset($this->localNames[$component_id][$local_name]), "Failed to get containing component for $id, local name $local_name not found for ID $component_id.");

          $component_id = $this->localNames[$component_id][$local_name];
        }

        $parent_name = $component_id;
      }
      elseif (substr($containing_component_token, 0, strlen('%self:')) == '%self:') {
        $path_string = substr($containing_component_token, strlen('%self:'));
        $path_pieces = explode(':', $path_string);

        $component_id = $id;
        foreach ($path_pieces as $path_piece) {
          $local_name = $path_piece;

          assert(isset($this->localNames[$component_id][$local_name]), "Failed to get containing component for $id, local name $local_name not found for ID $component_id.");

          $component_id = $this->localNames[$component_id][$local_name];
        }

        $parent_name = $component_id;
      }
      elseif (substr($containing_component_token, 0, strlen('%nearest_root:')) == '%nearest_root:') {
        $path_string = substr($containing_component_token, strlen('%nearest_root:'));
        $path_pieces = explode(':', $path_string);

        $component_id = $this->requestRoots[$id];
        foreach ($path_pieces as $path_piece) {
          $local_name = $path_piece;

          assert(isset($this->localNames[$component_id][$local_name]), "Failed to get containing component for $id, local name $local_name not found for ID $component_id.");

          $component_id = $this->localNames[$component_id][$local_name];
        }

        $parent_name = $component_id;
      }
      else {
        throw new \Exception("Unrecognized containing component token string '$containing_component_token' for component $id.");
      }

      assert(isset($this->components[$parent_name]), "Containing component '$parent_name' given by '$id' is not a component ID.");

      $this->tree[$parent_name][] = $id;
    }

    return $this->tree;
  }

  /**
   * Gets the containment tree, with components represented by request paths.
   *
   * @return array
   *  An array of the same structure as getContainmentTree() returns, but
   *  instead of using object keys, every component is represented by its
   *  request path.
   */
  public function getContainmentTreeWithRequestPaths() {
    $tree_by_path = [];

    $tree = $this->getContainmentTree();

    foreach ($tree as $parent => $children) {
      foreach ($children as $child) {
        $tree_by_path[$this->requestPaths[$parent]][] = $this->requestPaths[$child];
      }
    }

    return $tree_by_path;
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
    $component_id = $this->getComponentKey($component);

    $tree = $this->getContainmentTree();

    $child_ids = $tree[$component_id] ?? [];
    $return = [];
    foreach ($child_ids as $id) {
      $return[$id] = $this->components[$id];
    }

    return $return;
  }

  /**
   * Gets the containment tree.
   *
   * This checks that the tree has been assembled already.
   *
   * @return array
   *   The tree assembled by assembleContainmentTree().
   *
   * @throws \LogicException
   *   Throws an exception if the tree has not yet been assembled.
   */
  private function getContainmentTree() {
    if (is_null($this->tree)) {
      throw new \LogicException("Tree has not yet been assembled.");
    }

    return $this->tree;
  }

  /**
   * Get the component that matches the type and merge tag, if any.
   *
   * @param $component
   *   The component to find a match for, not yet in the collection.
   * @param $requesting_component
   *   The component that is requesting the component to match.
   *
   * @return
   *   Either a component that's in the collection and matches the given
   *   component, or NULL if nothing was found.
   */
  public function getMatchingComponent($component, $requesting_component) {
    if (is_null($requesting_component)) {
      return;
    }

    if (!$component->getMergeTag()) {
      return NULL;
    }

    // We've not added $component yet (and might not), so we don't know have
    // any data about it. So we need to use the requesting component to get the
    // closest requesting root.
    $requesting_component_id = $this->getComponentKey($requesting_component);
    if (isset($this->roots[$requesting_component_id])) {
      // If the requesting component is a root, it's obviously the closest
      // requesting root. But getClosestRequestingRootComponent() would not
      // give us this, as root's closest requester is not itself.
      $closest_requesting_root_id = $requesting_component_id;
    }
    else {
      $closest_requesting_root_id = $this->getComponentKey($this->getClosestRequestingRootComponent($requesting_component));
    }

    if (isset($this->mergeTags[$closest_requesting_root_id][$component->getType()][$component->getMergeTag()])) {
      $matching_component_id = $this->mergeTags[$closest_requesting_root_id][$component->getType()][$component->getMergeTag()];
      return $this->components[$matching_component_id];
    }
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
   *
   * @throws \LogicException
   *   Throws an exception if called with the root component, as in that case
   *   the answer does not make sense.
   */
  public function getClosestRequestingRootComponent(BaseGenerator $component) {
    if ($this->getComponentKey($component) === $this->rootGeneratorId) {
      throw new \LogicException("ComponentCollection::getClosestRequestingRootComponent() may not be called with the root component.");
    }

    $component_id = $this->getComponentKey($component);

    $closest_requesting_root_id = $this->requestRoots[$component_id];
    return $this->components[$closest_requesting_root_id];
  }

  /**
   * Dumps the data structures of the collection for debugging.
   */
  private function dumpStructure() {
    dump("Requesters:");
    dump($this->requesters);

    dump("Request roots:");
    dump($this->requestRoots);

    dump("Request paths:");
    dump($this->requestPaths);

    dump("Local names:");
    dump($this->localNames);

    if (isset($this->tree)) {
      dump("Containment tree:");
      dump($this->tree);
    }
  }

}
