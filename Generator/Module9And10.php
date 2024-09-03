<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\DeferredGeneratorDefinition;
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
  // TODO KILL
  public static function configurationDefinition(): PropertyDefinition {
    $definition = parent::configurationDefinition();

    // Remove the hook implementation type config setting, as OO hooks are new
    // in Drupal 11.
    $definition->removeProperty('hook_implementation_type');

    return $definition;
  }

  // remove hook type in props

}
