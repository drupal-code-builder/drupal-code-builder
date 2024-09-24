<?php

namespace DrupalCodeBuilder\Definition;

use DrupalCodeBuilder\Utility\InsertArray;
use MutableTypedData\Exception\InvalidDefinitionException;

/**
 * Provides methods to insert properties.
 */
trait PropertyInsertTrait {

  /**
   * Adds properties before the named property.
   *
   * @param string $before
   *   The name of the property to insert before.
   * @param \MutableTypedData\Definition\DataDefinition $properties
   *   The properties to insert before the specified property name, in the order
   *   to insert them.
   *
   * @return \MutableTypedData\Definition\DataDefinition
   *   Returns the same instance for chaining. (This is typed as the parent
   *   class so it can be moved to MTD in future without causing incompatibility
   *   issues.)
   */
  public function addPropertyBefore(string $before, PropertyDefinition ...$properties): self {
    $this->addPropertyHelper($before, TRUE, ...$properties);

    return $this;
  }

  /**
   * Adds a property after the named property.
   *
   * @param string $after
   *   The name of the property to insert after.
   * @param \MutableTypedData\Definition\DataDefinition $properties
   *   The properties to insert after the specified property name, in the order
   *   to insert them.
   *
   * @return \MutableTypedData\Definition\DataDefinition
   *   Returns the same instance for chaining. (This is typed as the parent
   *   class so it can be moved to MTD in future without causing incompatibility
   *   issues.)
   */
  public function addPropertyAfter(string $after, PropertyDefinition ...$properties): self {
    $this->addPropertyHelper($after, FALSE, ...$properties);

    return $this;
  }

  /**
   * Helper for inserting properties.
   *
   * @param string $existing
   *   The name of the property to insert before or after.
   * @param bool $before
   *   Whether to insert before or after.
   * @param \MutableTypedData\Definition\DataDefinition ...$properties
   *   The properties to insert after the specified property name, in the order
   *   to insert them.
   */
  protected function addPropertyHelper(string $existing, bool $before, PropertyDefinition ...$properties): void {
    if ($this instanceof PropertyDefinition) {
      // TODO! this won't catch child classes of SimpleData!!!
      if ($this->type == 'string' || $this->type == 'boolean') {
        // TODO: needs tests
        throw new InvalidDefinitionException("Simple data can't have sub-properties.");
      }

      if ($this->type == 'mutable') {
        throw new InvalidDefinitionException(sprintf(
          "Mutable data at %s must have only the type property set.",
          $this->name
        ));
      }
    }

    if (!$before) {
      // Reverse the array of properties, as we keep adding them after the
      // $existing property.
      $properties = array_reverse($properties);
    }

    $method_name = match ($before) {
      TRUE  => 'insertBefore',
      FALSE => 'insertAfter',
    };

    foreach ($properties as $property) {
      if (empty($property->getName())) {
        throw new InvalidDefinitionException("Properties added with addPropertyBefore() or addPropertyAfter() must have a machine name set.");
      }

      InsertArray::$method_name($this->properties, $existing, [
        $property->getName() => $property,
      ]);
    }
  }

}