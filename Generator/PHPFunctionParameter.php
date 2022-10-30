<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator class for a function parameter.
 */
class PHPFunctionParameter extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'parameter_name' => PropertyDefinition::create('string')
        ->setRequired(TRUE),
      'typehint' => PropertyDefinition::create('string'),
      'description' => PropertyDefinition::create('string')
        ->setRequired(TRUE),
      'class_name' => PropertyDefinition::create('string'),
      'method_name' => PropertyDefinition::create('string'),
    ]);

    return $definition;
  }

  public function getMergeTag() {
    // ARGH TODO also function name!
    return $this->component_data->class_name->value . '-' . $this->component_data->parameter_name->value;
  }

  // merge tag: unique to class + function + name.

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'parameter';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    $contents = [];

    foreach (['parameter_name', 'typehint', 'description'] as $property) {
      $contents[$property] = $this->component_data->{$property}->value;
    }

    return $contents;
  }

}
