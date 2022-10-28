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
      'name' => PropertyDefinition::create('string')
        ->setRequired(TRUE),
      'typehint' => PropertyDefinition::create('string'),
      'description' => PropertyDefinition::create('string')
        ->setRequired(TRUE),
    ]);

    return $definition;
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

    foreach (['name', 'typehint', 'description'] as $property) {
      $contents[$property] = $this->component_data->{$property}->value;
    }

    return $contents;
  }

}
