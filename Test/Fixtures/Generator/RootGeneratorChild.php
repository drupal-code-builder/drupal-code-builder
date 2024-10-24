<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator;


/**
 * Dummy generator class for tests.
 */
class RootGeneratorChild extends RootGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition($definition) {
    parent::addToGeneratorDefinition($definition);

    // Remove the property which the parent class set, which is not relevant
    // to this root generator.
    $definition->removeProperty('only_base');
  }

}
