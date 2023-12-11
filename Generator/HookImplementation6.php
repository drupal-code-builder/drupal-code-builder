<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator class for hook implementations for Drupal 6.
 */
class HookImplementation6 extends HookImplementation {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
    $definition = parent::getPropertyDefinition();

    $definition->getProperty('function_docblock_lines')->getDefault()
      ->setExpression("['Implementation of ' ~ get('..:hook_name') ~ '().']");
  }

}
