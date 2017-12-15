<?php

namespace DrupalCodeBuilder\Generator\Collection;

use DrupalCodeBuilder\Generator\BaseGenerator;

/**
 * The collection of components for a generate request.
 *
 * This holds the instantiated components, in several different structures:
 * - The linear list of components, which this class can iterate over.
 * - The tree of components arranged by request.
 * - The tree of components arranged by containment. This is only available
 *   once assembleContainmentTree() has been called. After this, no more
 *   components may be added.
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
   * @param $component
   *   The component to add.
   */
  public function addComponent(BaseGenerator $component, $requesting_component) {
    // Components may not be added once the collection is locked.
    if ($this->locked) {
      throw new \LogicException("Attempt to add component to locked collection.");
    }

    $key = $component->getUniqueID();

    // If this is the first component, it's the root.
    if (!isset($this->rootGeneratorId)) {
      $this->rootGeneratorId = $key;
    }

    if (isset($this->items[$key])) {
      throw new \Exception("Key $key already in use.");
    }

    if ($requesting_component && !isset($this->requesters[$key])) {
      // TODO: store multiple requesters?
      $this->requesters[$key] = $requesting_component->getUniqueID();
    }

    $this->components[$key] = $component;
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
      if (!empty($parent_name)) {
        assert(isset($this->components[$parent_name]), "Containing component '$parent_name' given by '$id' is not a component ID.");

        $this->tree[$parent_name][] = $id;
      }
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
    return $tree[$component_id];
  }

  /**
   * Gets the closest requester that is a root component.
   *
   * This may be called before the collection is complete.
   *
   * @param $component_id
   *   The ID of the component.
   *
   * @return
   *   The root component.
   */
  public function getClosestRequestingRootComponent($component_id) {
    // We're ascending a tree whose root is a root component, so we have to
    // find a root eventually.
    while (TRUE) {
      $requesting_component_id = $this->requesters[$component_id];
      $requesting_component = $this->components[$requesting_component_id];

      if ($requesting_component instanceof \DrupalCodeBuilder\Generator\RootComponent) {
        break;
      }

      $component_id = $requesting_component->getUniqueID();
    }

    return $requesting_component;
  }

}
