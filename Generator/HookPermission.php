<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\HookPermission.
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
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    // Set some default properties.
    $component_data += array(
      'hook_name' => 'hook_permission',
      // TODO: Remove.
      'permissions' => array(),
    );

    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    // If we have no children, i.e. no RouterItem components, then hand over to
    // the parent, which will output the default hook code.
    if (empty($children_contents)) {
      return parent::buildComponentContents($children_contents);
    }

    // TEMPORARY. This will be changed to it get passed in by Hooks when it
    // requests us.
    // Sanity checks already done at this point; no need to catch exception.
    $mb_task_handler_report = \ModuleBuilder\Factory::getTask('ReportHookData');
    $hook_function_declarations = $mb_task_handler_report->getHookDeclarations();
    $this->hook_info = $hook_function_declarations[$this->name];
    $this->component_data['doxygen_first'] = $this->hook_doxygen_text($this->hook_info['name']);
    $declaration = preg_replace('/(?<=function )hook/', '%module', $this->hook_info['definition']);
    $this->component_data['declaration'] = $declaration;

    $code = array();
    $code[] = '£permissions = array();';
    foreach ($children_contents as $menu_item_lines) {
      $code = array_merge($code, $menu_item_lines);
    }
    $code[] = '';
    $code[] = 'return £permissions;';

    $this->component_data['body_indent'] = 2;

    $this->component_data['body'] = $code;

    // TEMPORARY: set tripswitch for componentFunctions().
    $this->bypasscomponentFunctions = TRUE;

    return parent::buildComponentContents($children_contents);
  }

  /**
   * Called by ModuleCodeFile to collect functions from its child components.
   */
  public function componentFunctions() {
    // TEMPORARY. Needed while HookImplementation::componentFunctions() exists,
    // because we need PHPFunction::buildComponentContents() to call this and
    // get an empty array back.
    if (!empty($this->bypasscomponentFunctions)) {
      return array();
    }
    else {
      return parent::componentFunctions();
    }
  }

}
