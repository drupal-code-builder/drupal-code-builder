<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator class for a function line.
 */
class PHPFunctionLine extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition($definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      // The line of code, without function indentation.
      'code' => PropertyDefinition::create('string')
        ->setRequired(TRUE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'line';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    $contents = [];

    $contents[] = $this->component_data->code->value;

    return $contents;
  }

}
