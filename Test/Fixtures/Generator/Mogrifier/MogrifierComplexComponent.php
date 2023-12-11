<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator\Mogrifier;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

class MogrifierComplexComponent extends BaseGenerator {

  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
    $definition->addProperties([
      'string_property' => PropertyDefinition::create('string'),
      'recursive' => GeneratorDefinition::createFromGeneratorType('SubMogrifier')
        ->setLabel("Compound Generator"),

    ]);
  }

}