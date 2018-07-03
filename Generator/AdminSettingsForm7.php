<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Component generator: admin form for modules.
 */
class AdminSettingsForm7 extends Form7 {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $data_definition['code_file']['default'] = '%module.admin.inc';

    $data_definition['form_id']['default'] = function($component_data) {
      return $component_data['root_component_name'] . '_settings_form';
    };

    return $data_definition;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    // Change the body of the form builder.
    $form_name = $this->component_data['form_id'];
    $form_builder = $form_name;
    $form_validate  = $form_name . '_validate';
    $form_submit    = $form_name . '_submit';

    // Override the form builder's location and code.
    $components[$form_builder]['containing_component'] = '%requester:%module.admin.inc';
    $components[$form_builder]['body'] = array(
      "£form['%module_variable_foo'] = array(",
      "  '#type' => 'textfield',",
      "  '#title' => t('Foo'),",
      "  '#default_value' => variable_get('%module_variable_foo', 42),",
      "  '#required' => TRUE,",
      ");",
      "",
      "return system_settings_form(£form);",
    );

    // Remove the form validation and submit handlers, as Drupal core takes care
    // of this for system settings.
    unset($components[$form_validate]);
    unset($components[$form_submit]);

    // This takes care of adding hook_menu() and so on.
    $components['menu_item'] = array(
      'component_type' => 'RouterItem',
      'path' => 'admin/config/TODO-SECTION/%module',
      'title' => 'Administer %readable',
      'description' => 'Configure settings for %readable.',
      'page callback' => 'drupal_get_form',
      'page arguments' => "array('{$form_name}')",
      'access arguments' => "array('administer %module')",
      'file' => '%module.admin.inc',
    );

    $components['Permission'] = array(
      'component_type' => 'Permission',
      'permission' => 'administer %module',
    );

    $components['info_configuration'] = array(
      'component_type' => 'InfoProperty',
      'property_name' => 'configure',
      'property_value' => 'admin/config/TODO-SECTION/%module',
    );

    return $components;
  }

}
