<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Component generator: admin form for modules.
 */
class AdminSettingsForm7 extends Form7 {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->getProperty('code_file')->setLiteralDefault('%module.admin.inc');

    $definition->getProperty('form_id')->setDefault(
      DefaultDefinition::create()
        ->setExpression("get('..:root_component_name') ~ '_settings_form'")
        ->setDependencies('..:root_component_name')
      );

    return $definition;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // Change the body of the form builder.
    $form_name = $this->component_data['form_id'];
    $form_builder = $form_name;
    $form_validate  = $form_name . '_validate';
    $form_submit    = $form_name . '_submit';

    // Override the form builder's location and code.
    $components[$form_builder]['containing_component'] = '%requester:%module.admin.inc';
    $components[$form_builder]['body'] = [
      "£form['%module_variable_foo'] = [",
      "  '#type' => 'textfield',",
      "  '#title' => t('Foo'),",
      "  '#default_value' => variable_get('%module_variable_foo', 42),",
      "  '#required' => TRUE,",
      "];",
      "",
      "return system_settings_form(£form);",
    ];

    // Remove the form validation and submit handlers, as Drupal core takes care
    // of this for system settings.
    unset($components[$form_validate]);
    unset($components[$form_submit]);

    // This takes care of adding hook_menu() and so on.
    $components['menu_item'] = [
      'component_type' => 'RouterItem',
      'path' => 'admin/config/TODO-SECTION/%module',
      'title' => 'Administer %readable',
      'description' => 'Configure settings for %readable.',
      'page callback' => 'drupal_get_form',
      'page arguments' => "array('{$form_name}')",
      'access arguments' => "array('administer %module')",
      'file' => '%module.admin.inc',
    ];

    $components['Permission'] = [
      'component_type' => 'Permission',
      'permission' => 'administer %module',
    ];

    $components['info_configuration'] = [
      'component_type' => 'InfoProperty',
      'property_name' => 'configure',
      'property_value' => 'admin/config/TODO-SECTION/%module',
    ];

    return $components;
  }

}
