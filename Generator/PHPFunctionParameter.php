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
  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'parameter_name' => PropertyDefinition::create('string')
        ->setRequired(TRUE),
      'by_reference' => PropertyDefinition::create('boolean'),
      'typehint' => PropertyDefinition::create('string'),
      'description' => PropertyDefinition::create('string')
        ->setRequired(TRUE),
      'class_name' => PropertyDefinition::create('string'),
      'method_name' => PropertyDefinition::create('string'),
    ]);

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return implode('-', [
      $this->component_data->class_name->value,
      $this->component_data->method_name->value.
      $this->component_data->parameter_name->value,
    ]);
  }

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

    foreach (['parameter_name', 'typehint', 'description', 'by_reference'] as $property) {
      $contents[$property] = $this->component_data->{$property}->value;
    }

    return $contents;
  }

}
