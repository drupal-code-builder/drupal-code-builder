<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator;

use DrupalCodeBuilder\Generator\RootComponent;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Dummy generator class for tests.
 */
class RootGeneratorBase extends RootComponent {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'common' => PropertyDefinition::create('string'),
      'only_base' => static::getLazyDataDefinitionForGeneratorType('UnrelatedVersionComponent')
        ->setMultiple(TRUE),
    ]);

    return $definition;
  }

  public static function rootComponentPropertyDefinitionAlter(PropertyDefinition $definition): void {
    // Does nothing.
  }

  public static function addArrayPropertyInfoToDefinition(PropertyDefinition $definition, $array_property_info) {
    // Shut this shim up because it tries to get the Module generator!!
  }

}
