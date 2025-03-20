<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for PHP class constructor functions.
 *
 * This handles:
 * - Parameters getting promoted to properties, or assigned to properties. In
 *   the latter case, the property component is requested. With assignment to
 *   a property, the assignment can be with an expression rather than a direct
 *   setting.
 * - Parameters that are passed on to a parent call.
 *
 * Adds options for the parameters for constructor property promotion:
 *  - visibility: A string with the visibility for the promoted property.
 *  - readonly: A boolean indicating whether the promoted property should be
 *    declared as readonly.
 * - parent_call: A boolean indicating this parameter should be passed to a
 *   parent call.
 * - property_assignment: Options for assigning the parameter to a property.
 */
class PHPConstructor extends PHPFunction {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('function_name')
      ->setLiteralDefault('__construct');

    $definition->getProperty('prefixes')
      ->setLiteralDefault(['public']);

    // Because constructors don't inherit, we can use type declarations for
    // primitives.
    $definition->getProperty('use_primitive_parameter_type_declarations')
      ->setLiteralDefault(TRUE);

    // Sets all properties that are set to be assigned to use property
    // promotion.
    $definition->addProperty(PropertyDefinition::create('boolean')
      ->setName('promote_properties')
    );

    // Faffy special case for YAML plugin managers.
    $definition->addProperty(PropertyDefinition::create('string')
      ->setMultiple(TRUE)
      ->setName('initial_comments')
    );

    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('class_name')
    );

    $definition->getProperty('parameters')
      ->addProperties([
        'visibility' => PropertyDefinition::create('string'),
        'readonly' => PropertyDefinition::create('boolean'),
        'parent_call' => PropertyDefinition::create('boolean'),
        'property_assignment' => PropertyDefinition::create('complex')
          ->addProperties([
            // The name is required as a minimum to use promotion. Other values
            // are only necessary if there is an assignment expression which
            // means that the description and type of the assigned property are
            // different from the parameter.
            'name' => PropertyDefinition::create('string')
              ->setRequired(TRUE),
            'description' => PropertyDefinition::create('string'),
            'type' => PropertyDefinition::create('string'),
            'assignment_expression' => PropertyDefinition::create('string'),
          ]),
        'promote' => PropertyDefinition::create('boolean')
          // A particular parameter gets promoted if:
          // - This generator is set to promote parameters
          // - The parameter has the 'property_assignment:name' set
          // - The paramter does not have the
          //   'property_assignment:assignment_expression' set.
          ->setExpressionDefault("get('..:..:..:promote_properties') && get('..:property_assignment:name') && !get('..:property_assignment:assignment_expression')"),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return $this->component_data->class_name->value . $this->component_data->function_name->value;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    // Override the set value with configuration. Can't do this in defaults, as
    // the configuration value doesn't exist on all versions of the Module
    // component.
    if ($this->component_data->getItem('module:configuration:property_promotion')->value) {
      $this->component_data->promote_properties->value = TRUE;
    }

    // Access to get the default.
    // TODO: Remove this when buildMethodDeclaration() is changed to use
    // the parameter data item instead of an export array.
    foreach ($this->component_data->parameters as $parameter_data) {
      $parameter_data->promote->value;
    }

    $components = parent::requiredComponents();

    // Parameters that assign to a property request that property, unless we're
    // set to promote properties.
    foreach ($this->component_data->parameters as $parameter_data) {
      if (!$parameter_data->promote->value && !$parameter_data->property_assignment->name->isEmpty()) {
        $components[$parameter_data->property_assignment->name->value] = [
          'component_type' => 'PHPClassProperty',
          // We only need to go up twice because we get requested by the class,
          // even if we're also requested through a longer chain by injected
          // services.
          'containing_component' => '%requester:%requester',
          'class_name' => $this->component_data->class_name->value,
          'property_name' => $parameter_data->property_assignment->name->value,
          'type' => $parameter_data->property_assignment->type->value,
          'docblock_first_line' => $parameter_data->property_assignment->description->value,
          'visibility' => 'protected',
        ];
      }
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    // Massage the parameters to handle promotion. First, make an array of the
    // promoted parameters, with their original parameter names (that is, the
    // snake_case parameter name, not the camelCase property name). This is to
    // match with any identical parameter that is used with an assignment
    // expression.
    $seen_promoted = [];
    foreach ($this->component_data->parameters as $index => $parameter) {
      if ($parameter->promote->value) {
        $seen_promoted[$parameter->name->value] = $parameter->property_assignment->name->value;
      }
    }

    // Change the parameter name to the property name for promoted parameters,
    // and parameters that match a promoted parameters. If an assigned parameter
    // is also used for a parameter with extraction, the docblock and the
    // declaration building code handes collapsing them.
    foreach ($this->component_data->parameters as $parameter) {
      if (isset($seen_promoted[$parameter->name->value])) {
        $parameter->name = $seen_promoted[$parameter->name->value];
      }
    }

    return parent::getContents();
  }

  /**
   * {@inheritdoc}
   */
  protected function buildParameter(array $parameter_info): string {
    $parameter_string = parent::buildParameter($parameter_info);

    $prefixes = [];
    if (isset($parameter_info['visibility'])) {
      $prefixes[] = $parameter_info['visibility'];
    }
    elseif ($parameter_info['promote']) {
      // Promotion requires a visibility prefix. Assume protected.
      $prefixes[] = 'protected';
    }

    if (!empty($parameter_info['readonly'])) {
      $prefixes[] = 'readonly';
    }

    if ($prefixes) {
      $parameter_string = implode(' ', $prefixes) . ' ' . $parameter_string;
    }

    return $parameter_string;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFunctionBody(): array {
    $body = [];

    foreach ($this->component_data->initial_comments as $comment_line) {
      $body[] = $comment_line->value;
    }

    // Parent call, if required.
    $parent_call_args = [];
    foreach ($this->component_data->parameters as $parameter_data) {
      if ($parameter_data->parent_call->value) {
        $parent_call_args[] = '$' . $parameter_data->name->value;
      }
    }

    if ($parent_call_args) {
      $body[] = 'parent::__construct(' . implode(', ', $parent_call_args) . ');';
    }

    foreach ($this->component_data->parameters as $parameter_data) {
      // Write an assignment line the parameter is assigned and is not promoted.
      if (!$parameter_data->promote->value && $parameter_data->property_assignment->name->value) {
        $assignment_line =
          '$this->' . $parameter_data->property_assignment->name->value .
          ' = ' .
          (
            $parameter_data->property_assignment->assignment_expression->value ?:
            '$' . $parameter_data->name->value
          ) .
          ';';

        $body[] = $assignment_line;
      }
    }

    // Add body from the parent.
    $body = array_merge($body, parent::getFunctionBody());

    return $body;
  }

}
