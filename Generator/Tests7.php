<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Component generator: tests.
 */
class Tests7 extends Tests {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
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

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $file = parent::getFileInfo();

    // Change the file location for D7.
    $file['path'] = 'tests';
    $file['filename'] = "%module.test";

    return $file;
  }

}
