<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\AdminSettingsForm.
 */

namespace ModuleBuider\Generator;

/**
 * Component generator: admin form for modules.
 *
 * TODO:
 *  - full menu item definition
 *  - line in .info file giving the config path
 */
class AdminSettingsForm extends Form {

  /**
   * Return an array of subcomponent types.
   */
  protected function requiredComponents() {
    $components = parent::requiredComponents();

    // This takes care of adding hook_menu() and so on.
    $components['admin/config/%module'] = array(
      'component_type' => 'RouterItem',
      'title' => 'Administer %module',
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
      "return system_settings_form(£form);",
    );

    $functions = parent::componentFunctions();

    return $functions;
  }

}
