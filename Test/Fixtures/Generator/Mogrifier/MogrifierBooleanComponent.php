<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator\Mogrifier;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

class MogrifierBooleanComponent extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition($definition) {
    $definition->addProperties([
      'string_property' => PropertyDefinition::create('string')
        ->setLabel("Should not be seen in Mogrifier data definition!"),
    ]);
  }

}
