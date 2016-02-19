<?php

/**
 * @file
 * Definition of ModuleBuilder\Generator\AdminSettingsForm.
 */

namespace ModuleBuilder\Generator;

/**
 * Component generator: admin form for modules.
 *
 * TODO:
 *  - full menu item definition
 *  - line in .info file giving the config path
 */
class AdminSettingsForm extends Form {

  /**
   * Override the parent to set the code file.
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    // Set some default properties.
    $component_data += array(
      'code_file' => '%module.admin.inc',
    );

    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
  }

  /**
   * @inheritdoc
   */
  public static function requestedComponentHandling() {
    return 'singleton';
  }

  /**
   * Return an array of subcomponent types.
   */
  protected function requiredComponents() {
    $components = parent::requiredComponents();

    // Change the body of the form builder.
    $form_name = $this->getFormName();
    $form_builder = $form_name;
    $form_validate  = $form_name . '_validate';
    $form_submit    = $form_name . '_submit';

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
    $form_name = $this->getFormName();
    $components['admin/config/TODO-SECTION/%module'] = array(
      'component_type' => 'RouterItem',
      'title' => 'Administer %readable',
      'description' => 'Configure settings for %readable.',
      'page callback' => 'drupal_get_form',
      'page arguments' => "array('{$form_name}')",
      'access arguments' => "array('administer %module')",
      'file' => '%module.admin.inc',
    );

    $components['Permissions'] = array(
      'component_type' => 'Permissions',
      'request_data' => array(
        'administer %module',
      ),
    );

    return $components;
  }

  /**
   * Called by ModuleCodeFile to collect functions from its child components.
   */
  public function componentFunctions() {
    // Override the default code bodies.
    $this->component_data['form_code_bodies']['builder'] = array(
      "£form['%module_variable_foo'] = array(",
      "  '#type' => 'textfield',",
      "  '#title' => t('Foo'),",
      "  '#default_value' => variable_get('%module_variable_foo', 42),",
      "  '#required' => TRUE,",
      ");",
      "",
      "// TODO! You probably don't need validation or submit handlers if using system_settings_form().",
      "return system_settings_form(£form);",
    );

    $functions = parent::componentFunctions();

    return $functions;
  }

  /**
   * The name of the form.
   */
  protected function getFormName() {
    // TODO: this should be set in our data.
    $root_component_data = $this->getRootComponentData();
    $base_component_name = $root_component_data['root_name'];
    return "{$base_component_name}_settings_form";
  }

}
