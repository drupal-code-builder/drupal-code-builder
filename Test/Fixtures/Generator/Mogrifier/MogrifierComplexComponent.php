<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator\Mogrifier;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

class MogrifierComplexComponent extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition($definition) {
    $definition->addProperties([
      'string_property' => PropertyDefinition::create('string'),
      'recursive' => GeneratorDefinition::createFromGeneratorType('SubMogrifier')
        ->setLabel("Compound Generator"),

    ]);
  }

}