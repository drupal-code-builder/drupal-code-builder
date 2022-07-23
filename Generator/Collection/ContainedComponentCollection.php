<?php

namespace DrupalCodeBuilder\Generator\Collection;

/**
 * Collection of components which are contained by another.
 *
 * This can be accessed as an array, keyed first by content type then by
 * request path.
 *
 * This is used only for
 * \DrupalCodeBuilder\Generator\BaseGenerator::$containedComponents and serves
 * to add guards and defaults in addition to normal array behaviour.
 */
class ContainedComponentCollection implements \ArrayAccess {

  protected $containedComponents;

  protected $requestPath;

  /**
   * Constructor.
   *
   * @param array $contained_components
   *   The array of contained components, keyed first by content type then by
   *   request path.
   * @param string $component_request_path
   *   The request path of the containing component. Used for debugging.
   */
  public function __construct(array $contained_components, string $component_request_path) {
    $this->containedComponents = $contained_components;

    // Check all keys are non-empty.
    assert(count($contained_components) == count(array_filter(array_keys($contained_components))));

    $this->requestPath = $component_request_path;
  }

  /**
   * Determines whether the collection is empty.
   *
   * @return bool
   *   Whether the collection contains any components or not.
   */
  public function isEmpty(): bool {
    return empty($this->containedComponents);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists(mixed $offset): bool {
    return isset($this->containedComponents[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet(mixed $offset): mixed {
    // Default to an empty array if there are no components of the given content
    // type.
    return $this->containedComponents[$offset] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet(mixed $offset, mixed $value): void {
    throw new \Exception("Items cannot be added to ContainedComponentCollection after creation.");
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset(mixed $offset): void {
    throw new \Exception("Items cannot be removed from ContainedComponentCollection after creation.");
  }

}
