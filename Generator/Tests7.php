<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Component generator: tests.
 */
class Tests7 extends Tests {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('filename')
      ->setLiteralDefault('tests/%module.test');
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // Declare the class file in the module's .info file.
    $components['info_class'] = [
      'component_type' => 'InfoProperty',
      'property_name' => 'files[]',
      'property_value' => 'tests/%module.test',
    ];
    return $components;
  }

}
