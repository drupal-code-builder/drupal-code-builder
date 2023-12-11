<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator\Mogrifier;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

class MogrifierComplexComponent extends BaseGenerator {

  // // TODO rename to  getGeneratorDataDefinition() when that is removed?
  // // KILL??
  // function getDataDefinition() {
  //   return PropertyDefinition::create('complex')
  //     ->setProperties([

  //     ]);
  // }

  public static function addProperties(PropertyDefinition $definition) {
    $definition->addProperties([
      'string_property' => PropertyDefinition::create('string'),
      'recursive' => GeneratorDefinition::createFromGeneratorType('SubMogrifier')
        ->setLabel("Compound Generator"),

    ]);
  }

}