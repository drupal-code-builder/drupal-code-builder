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
 *
 * TODO: not entirely sure we need this in addition to InjectedService.
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
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $parameter_data = [
      'name' => $this->component_data->parameter_name->value,
      'typehint' => $this->component_data->type->value,
      'description' => $this->component_data->description->value,
    ];

    if (!$this->component_data->omit_assignment->value) {
      $parameter_data['property_assignment'] = [
        'name' => $this->component_data->property_name->value,
        'type' => $this->component_data->property_type->value
          ?: $this->component_data->type->value,
        'description' => $this->component_data->property_description->value
          ?: $this->component_data->description->value,
      ];

      if ($this->component_data->expression->value) {
        $parameter_data['property_assignment']['assignment_expression'] = $this->component_data->expression->value;
      }
    }

    // This will merge with the constructor requested by the class.
    $components['construct'] = [
      'component_type' => 'PHPConstructor',
      'class_name' => $this->component_data->class_name->value,
      'parameters' => [
        0 => $parameter_data,
      ],
    ];

    return $components;
  }

}
