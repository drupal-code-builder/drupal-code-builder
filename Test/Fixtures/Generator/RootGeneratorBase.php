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
  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'common' => PropertyDefinition::create('string'),
      'only_base' => static::getLazyDataDefinitionForGeneratorType('UnrelatedVersionComponent')
        ->setMultiple(TRUE),
    ]);
  }

  public static function rootComponentPropertyDefinitionAlter(PropertyDefinition $definition): void {
    // Does nothing.
  }

}
