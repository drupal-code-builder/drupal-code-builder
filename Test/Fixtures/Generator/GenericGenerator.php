<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Dummy generator class for tests.
 *
 * Empty child class of BaseGenerator that can be instantiated and allows tests
 * to define components with arbitrary property definitions.
 */
class GenericGenerator extends BaseGenerator {

  /**
   * The property definition.
   *
   * @var \DrupalCodeBuilder\Definition\PropertyDefinition
   */
  protected static PropertyDefinition $definition;

  /**
   * Sets the property definition for this generator.
   *
   * @param \DrupalCodeBuilder\Definition\PropertyDefinition $definition
   *   The property definition.
   */
  public static function setPropertyDefinition(PropertyDefinition $definition) {
    static::$definition = $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    return static::$definition;
  }

}
