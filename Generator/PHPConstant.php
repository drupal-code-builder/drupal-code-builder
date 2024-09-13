<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\DocBlock;

/**
 * Generator for PHP constants.
 */
class PHPConstant extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'name' => PropertyDefinition::create('string')
        ->setLabel('Constant name')
        ->setRequired(TRUE)
        ->setValidators('machine_name'),
      'value' => PropertyDefinition::create('string')
        ->setLabel('Constant value')
        ->setRequired(TRUE),
      'type' => PropertyDefinition::create('string')
        ->setLabel('Data type')
        ->setRequired(TRUE),
      'docblock_lines' => PropertyDefinition::create('mapping')
        ->setRequired(TRUE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'constant';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    $docblock = Docblock::constant();
    foreach ($this->component_data->docblock_lines->export() as $line) {
      $docblock[] = $line;
    }

    $docblock->var($this->component_data->type->value);

    $lines = $docblock->render();

    $value = $this->component_data->value->value;

    if (!is_numeric($value)) {
      $value = "'$value'";
    }

    $lines[] = 'const ' . $this->component_data->name->value . ' = ' . $value . ';';

    return $lines;
  }

}
