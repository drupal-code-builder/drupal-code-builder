<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Drupal 8 version of component.
 */
class Module8 extends Module {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'tests' => static::getLazyDataDefinitionForGeneratorType('Tests', 'boolean')
        ->setLabel("Simpletest test case class")
        ->setDescription('NOTICE: These are deprecated in Drupal 8.'),
    ]);

    $definition->removeProperty('lifecycle');

    return $definition;
  }

}
