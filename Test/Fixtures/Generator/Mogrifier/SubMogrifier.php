<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator\Mogrifier;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\PropertyDefinition;

class SubMogrifier extends Mogrifier {

  public static function addProperties(PropertyDefinition $definition) {
    parent::addProperties($definition);

    // Remove the property that would cause circularity - this is the equivalent
    // of TestModule removing the tests property.
    $definition->removeProperty('complex_generator_property');

    // Remove the mutable property just to keep it simple.
    $definition->removeProperty('mutable_generator_property');
  }

}