<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for a property that's set by a constructor.
 *
 * This is a bit fuzzy, and also covers properties that are extracted from a
 * constructor parameter, in the case of injected pseudoservices.
 */
class PHPClassConstructorSetProperty extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'property_name' => PropertyDefinition::create('string')
        ->setLabel('Property name')
        ->setRequired(TRUE),
      'parameter_name' => PropertyDefinition::create('string')
        ->setLabel('Parameter name')
        ->setRequired(TRUE),
      'type' => PropertyDefinition::create('string')
        ->setLabel('Type')
        ->setRequired(TRUE),
      'description' => PropertyDefinition::create('string')
        ->setLabel('Description')
        ->setRequired(TRUE),
      'class_name' => PropertyDefinition::create('string')
        ->setRequired(TRUE),
      // Assignment expression if not just the plain parameter. Must not include
      // terminal ';'.
      'expression' => PropertyDefinition::create('string')
        ->setLabel('Expression'),
      // Allows special cases for assignment in the construct method.
      // TODO: Remove when PluginTypeManager is refactored to use this.
      'omit_assignment' => PropertyDefinition::create('boolean'),
      // Overrides for a property that differs from the constructor parameter.
      'property_description' => PropertyDefinition::create('string')
        ->setLabel('Property description override')
        ->setExpressionDefault("parent.description.get()"),
      'property_type' => PropertyDefinition::create('string')
        ->setLabel('Property type override')
        ->setExpressionDefault("parent.type.get()"),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    // Merge on the property name, as the parameter might repeat, e.g. in the
    // (slightly degenerate) case where we have an $entity_type_manager and also
    // a storage obtained from it.
    return $this->component_data->class_name->value . '-' . $this->component_data->property_name->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'constructor_set_property';
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $components['constructor_param'] = [
      'component_type' => 'PHPFunctionParameter',
      'containing_component' => '%requester:%requester:%requester:construct',
      'parameter_name' => $this->component_data->parameter_name->value,
      'typehint' => $this->component_data->type->value,
      'description' => $this->component_data->description->value,
      'class_name' => $this->component_data->class_name->value,
      'method_name' => '__construct',
    ];

    if ($this->component_data->omit_assignment->isEmpty()) {
      $code_line =
        '$this->' . $this->component_data->property_name->value .
        ' = ' .
        (
          $this->component_data->expression->value ?:
          '$' . $this->component_data->parameter_name->value
        ) .
        ';';

      $components['constructor_line'] = [
        'component_type' => 'PHPFunctionBodyLines',
        'containing_component' => '%requester:%requester:%requester:construct',
        'code' => $code_line,
      ];
    }

    return $components;
  }

}
