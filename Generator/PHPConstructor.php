<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for PHP class constructor functions.
 *
 * Adds options for the parameters for constructor property promotion:
 *  - visibility: A string with the visibility for the promoted property.
 *  - readonly: A boolean indicating whether the promoted property should be
 *    declared as readonly.
 */
class PHPConstructor extends PHPFunction {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('function_name')
      ->setLiteralDefault('__construct');

    $definition->getProperty('prefixes')
      ->setLiteralDefault(['public']);

    // Because constructors don't inherit, we can use type declarations for
    // primitives.
    $definition->getProperty('use_primitive_parameter_type_declarations')
      ->setLiteralDefault(TRUE);

    $definition->getProperty('parameters')
      ->addProperties([
        'visibility' => PropertyDefinition::create('string'),
        'readonly' => PropertyDefinition::create('boolean'),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildParameter(array $parameter_info): string {
    $parameter_string = parent::buildParameter($parameter_info);

    $prefixes = [];
    if (isset($parameter_info['visibility'])) {
      $prefixes[] = $parameter_info['visibility'];
    }
    if (!empty($parameter_info['readonly'])) {
      $prefixes[] = 'readonly';
    }

    if ($prefixes) {
      $parameter_string = implode(' ', $prefixes) . ' ' . $parameter_string;
    }

    return $parameter_string;
  }

}
