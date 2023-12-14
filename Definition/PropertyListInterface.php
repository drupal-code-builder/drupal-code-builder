<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\DataDefinition;

/**
 * Interface for definitions which hold lists of properties.
 *
 * This exists to be a parameter type for addToGeneratorDefinition().
 *
 * TODO: Move this to MTD? Add the fluid interface methods at that time - these
 * are problematic currently.
 */
interface PropertyListInterface {

  // public function setProperties(array $properties): static;
  // public function addProperty(DataDefinition $property): static;
  // public function addProperties(array $properties): static;

  /**
   * Gets a child property definition.
   *
   * @param string $name
   *  The property name.
   *
   * @return static
   *  The definition.
   *
   * @throws \Exception
   *  Throws an exception if the property doesn't exit.
   */
  public function getProperty(string $name): DataDefinition;

  /**
   * Adds properties to this definition.
   *
   * @param array $properties
   *  An array of data definitions. These are appended to existing properties.
   *  If any keys in this array correspond to existing properties, the existing
   *  definition is overwritten. The replacement property will be in the order
   *  given in the $properties array, not in its original position.
   *
   * @return static
   */
  public function getProperties();

}
