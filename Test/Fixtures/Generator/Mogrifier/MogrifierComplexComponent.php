<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator\Mogrifier;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\PropertyDefinition;

class MogrifierComplexComponent extends BaseGenerator {

  // TODO rename to  getGeneratorDataDefinition() when that is removed?
  function getDataDefinition() {
    return PropertyDefinition::create('complex')
      ->setProperties([

      ]);
  }

}