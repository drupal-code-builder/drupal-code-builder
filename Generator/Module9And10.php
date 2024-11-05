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

    $definition->getProperty('hook_implementation_type')->setOptionsArray([
      'procedural' => 'Functions in procedural files, such as .module',
      'oo_legacy' => 'Class methods on a Hooks class, with legacy support for Drupal core < 11.1',
    ]);
  }

}
