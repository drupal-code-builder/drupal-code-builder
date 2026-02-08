<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a validation constraint plugin.
 *
 * This needs a special case as two classes are needed: the plugin itself, and
 * a Symfony validator.
 *
 * This is a variant generator for the Plugin generator, and should not be
 * used directly.
 */
class PluginValidationConstraint extends PluginClassDiscoveryHybrid {

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $components = [];

    $components['validator'] = [
      'component_type' => 'PHPClassFileWithInjection',
      'plain_class_name' => $this->component_data['plain_class_name'] . 'Validator',
      'injected_services' => $this->component_data->injected_services->values(),
      'use_static_factory_method' => TRUE,
      'relative_namespace' => $this->component_data['relative_namespace'],
      'parent_class_name' => '\Symfony\Component\Validator\ConstraintValidator',
      'docblock_first_line' => "Validates the {$this->component_data['plain_class_name']} constraint.",
      // TODO: validation message property. Needs the mishmash around components
      // and contents to be resolved first!
      // See https://github.com/drupal-code-builder/drupal-code-builder/issues/134
    ];

    // Zap the injected services set here, as we don't want the plugin class to
    // have any DI.
    $this->component_data->injected_services = [];

    $components += parent::requiredComponents();

    $components['validator_validate'] = [
      'component_type' => 'PHPFunction',
      'function_name' => 'validate',
      'containing_component' => '%requester:validator',
      'docblock_inherit' => TRUE,
      'function_name' => 'validate',
      'declaration' => 'public function validate($items, \Symfony\Component\Validator\Constraint $constraint)',
      'body' => [
        '// Fail validation.',
        "£this->context->addViolation(£constraint->myFailure, ['%value' => 'my bad value']);",
      ],
    ];

    return $components;
  }

}
