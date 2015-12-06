<?php

/**
 * @file
 * Definition of ModuleBuilder\Generator\HookPermission.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for hook_permission() implementation.
 */
class HookPermission extends HookImplementation {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   *
   * A HookImplementation generator should use as its name the full hook name,
   * e.g., 'hook_menu'.
   */
  public $name = 'hook_permission';

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values.
   *   Valid properties are:
   *      - 'permissions': (optional) An array of permission names.
   */
  function __construct($component_name, $component_data = array()) {
    // Set some default properties.
    $component_data += array(
      'permissions' => array(),
    );

    parent::__construct($component_name, $component_data);
  }

  /**
   * Called by ModuleCodeFile to collect functions from its child components.
   */
  public function componentFunctions() {
    // Get the function data from our parent class first.
    $return = parent::componentFunctions();

    // If we were requested without any permissions, just return template code.
    if (empty($this->component_data['permissions'])) {
      return $return;
    }

    // Otherwise, replace the template code with the permissions.
    // TODO: this is the same pattern as the HookMenu generator: generalize
    // this in a single class?
    $code = array();

    // Opening lines.
    // DX sugar: use £ for variables in the template code.
    $code[] = "£permissions = array();";
    $code[] = "";

    foreach ($this->component_data['permissions'] as $permission_name) {
      $code[] = "£permissions['$permission_name'] = array(";
      $code[] = "  'title' => t('TODO: enter permission title'),";
      $code[] = "  'description' => t('TODO: enter permission description'),";
      $code[] = ");";
    }

    $code[] = "return £permissions;";

    $return[$this->name]['code'] = $code;

    // We return an array of lines, so we need newlines at start and finish.
    $return[$this->name]['has_wrapping_newlines'] = FALSE;

    return $return;
  }

}
