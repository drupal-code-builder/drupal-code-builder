<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\HookImplementation.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for a single hook implementation.
 */
class HookImplementation extends PHPFunction {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   *
   * A HookImplementation generator should use as its name the full hook name,
   * e.g., 'hook_menu'.
   */
  public $name;

  /**
   * The data for this hook, from the ReportHookData task.
   *
   * @see getHookDeclarations() for format.
   */
  protected $hook_info;

  /**
   * Constructor.
   *
   * @param $component_name
   *  The name of a function component should be its function (or method) name.
   * @param $component_data
   *   An array of data for the component. Any missing properties are given
   *   default values. Valid properties in addition to those from parent classes
   *   are:
   *     - 'hook_name': The full name of the hook.
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    // Set defaults.
    $component_data += array(
      'doxygen_first' => $this->hook_doxygen_text($component_data['hook_name']),
    );

    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
  }


  /**
   * Declares the subcomponents for this component.
   *
   * These are not necessarily child classes, just components this needs.
   *
   * A hook implementation adds the module code file that it should go in. It's
   * safe for the same code file to be requested multiple times by different
   * hook implementation components.
   *
   * @return
   *  An array of subcomponent names and types.
   */
  protected function requiredComponents() {
    // Sanity checks already done at this point; no need to catch exception.
    $mb_task_handler_report = \ModuleBuilder\Factory::getTask('ReportHookData');
    $hook_function_declarations = $mb_task_handler_report->getHookDeclarations();
    //drush_print_r($hook_function_declarations[$this->name]);

    $this->hook_info = $hook_function_declarations[$this->name];

    $filename = $hook_function_declarations[$this->name]['destination'];

    $this->code_file = $filename;

    return array(
      $filename => 'ModuleCodeFile',
    );
  }

  /**
   * Return this component's parent in the component tree.
   */
  function containingComponent() {
    return $this->code_file;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    // Replace the 'hook_' part of the function declaration.
    $this->component_data['declaration'] = preg_replace('/(?<=function )hook/', '%module', $this->component_data['declaration']);

    // TODO: get the hook template from user-defined stuff.
    /*
    // Old code, adapt here:
    // See if function bodies exist; if so, use function bodies from template
    if (isset($hook['template'])) {
      // Strip out INFO: comments for advanced users
      if (!\ModuleBuilder\Factory::getEnvironment()->getSetting('detail_level', 0)) {
        // Used to strip INFO messages out of generated file for advanced users.
        $pattern = '#\s+/\* INFO:(.*?)\*FILLERDONTCLOSECOMMENT/#ms';
        $hook['template'] = preg_replace($pattern, '', $hook['template']);
      }
      //dsm($hook);

      $function_code .= $hook['template'];
    }
    */

    return parent::buildComponentContents($children_contents);
  }

  /**
   * Make the doxygen first line for a given hook.
   *
   * @param
   *   The long hook name, eg 'hook_menu'.
   */
  function hook_doxygen_text($hook_name) {
    return "Implements $hook_name().";
  }

}
