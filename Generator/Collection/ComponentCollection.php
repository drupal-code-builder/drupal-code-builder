<?php

namespace DrupalCodeBuilder\Generator\Collection;

use DrupalCodeBuilder\Generator\BaseGenerator;

/**
 * The collection of components for a generate request.
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
  public function addComponent(BaseGenerator $component) {
    $key = $component->getUniqueID();

    if (isset($this->items[$key])) {
      throw new \Exception("Key $key already in use.");
    }

    $this->components[$key] = $component;
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

}
