<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator\Mogrifier;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

class MogrifierBooleanComponent extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected static $dataType = 'boolean';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
    $definition->addProperties([
      'string_property' => PropertyDefinition::create('string'),
    ]);
  }

}