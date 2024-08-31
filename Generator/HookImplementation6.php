<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator class for hook implementations for Drupal 6.
 */
class HookImplementation6 extends HookImplementationProcedural {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('function_docblock_lines')->getDefault()
      ->setExpression("['Implementation of ' ~ get('..:hook_name') ~ '().']");
  }

}
