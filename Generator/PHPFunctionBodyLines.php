<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator class for a function line.
 */
class PHPFunctionBodyLines extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      // The line of code, without function indentation.
      'code' => PropertyDefinition::create('string')
        ->setMultiple(TRUE),
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
    return $this->component_data->code->values();
  }

}
