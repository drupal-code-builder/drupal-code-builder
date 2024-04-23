<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for PHP constants.
 */
class PHPConstant extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'name' => PropertyDefinition::create('string')
        ->setLabel('Constant name')
        ->setRequired(TRUE)
        ->setValidators('machine_name'),
      'value' => PropertyDefinition::create('string')
        ->setLabel('Constant value')
        ->setRequired(TRUE),
      'type' => PropertyDefinition::create('string')
        ->setLabel('Data type')
        ->setRequired(TRUE),
      'docblock_lines' => PropertyDefinition::create('mapping'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'constant';
  }

}
