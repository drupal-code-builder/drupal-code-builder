<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\SimpleGeneratorDefinition;

/**
 * Drupal 8 version of component.
 */
class Module8 extends Module {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition($definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'tests' => SimpleGeneratorDefinition::createFromGeneratorType('Tests', 'boolean')
        ->setLabel("Simpletest test case class")
        ->setDescription('NOTICE: These are deprecated in Drupal 8.'),
    ]);

    $definition->removeProperty('lifecycle');
  }

}
