<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use PHPUnit\Framework\Assert;

/**
 * Dummy generator class for tests.
 */
class UnrelatedVersionComponent extends BaseGenerator {

  /**
   * {@inheritdoc}
  */
  public static function addToGeneratorDefinition($definition) {
    // We should not get here when getting properties for RootGeneratorChild,
    // because this generator is unrelated and might do things here which are
    // not possible in RootGeneratorChild's Drupal version environment.
    Assert::fail("The UnrelatedVersionComponent generator's addToGeneratorDefinition() should not be called.");
  }

}
