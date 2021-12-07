<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Dummy generator class for tests.
 */
class RootGeneratorChild extends RootGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    // Remove the property which the parent class set, which is not relevant
    // to this root generator.
    $definition->removeProperty('only_base');

    return $definition;
  }

}
