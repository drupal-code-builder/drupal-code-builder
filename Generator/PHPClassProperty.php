<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for PHP class properties.
 */
class PHPClassProperty extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'class_name' => PropertyDefinition::create('string'),
      'property_name' => PropertyDefinition::create('string'),
      'type' => PropertyDefinition::create('string'),
      'docblock_first_line' => PropertyDefinition::create('string'),
      'visibility' => PropertyDefinition::create('string'),
      'static' => PropertyDefinition::create('boolean')
        ->setLiteralDefault(FALSE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return $this->component_data->class_name->value . $this->component_data->property_name->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'class_property';
  }

}
