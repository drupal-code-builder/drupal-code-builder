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
   * @return \MutableTypedData\Definition\DataDefinition
   *  The definition.
   *
   * @throws \Exception
   *  Throws an exception if the property doesn't exit.
   */
  public function getProperty(string $name): DataDefinition;

  /**
   * Gets this defintion's properties.
   *
   * @return \MutableTypedData\Definition\DataDefinition[]
   *  An array of property definitions, keyed by the property name.
   */
  public function getProperties();

}
