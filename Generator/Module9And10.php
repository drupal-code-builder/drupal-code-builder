<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Attribute\DrupalCoreVersion;
use DrupalCodeBuilder\Attribute\RelatedBaseClass;

/**
 * Drupal 9 and 10 version of component.
 */
#[DrupalCoreVersion(10)]
#[DrupalCoreVersion(9)]
#[RelatedBaseClass('Module')]
class Module9And10 extends Module {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    // Make the hook implementation type internal with a default value, as OO
    // hooks are new in Drupal 11.
    $definition->getProperty('hook_implementation_type')
      ->setInternal(TRUE)
      ->setLiteralDefault('procedural');
  }

}
