<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\HookImplementation.
 */

namespace ModuleBuider\Generator;

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
    // TODO! Caching!
    $mb_factory = module_builder_get_factory();
    // Sanity checks already done at this point; no need to catch exception.
    $mb_task_handler_report = $mb_factory->getTask('ReportHookData');
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
   * Called by ModuleCodeFile to collect functions from its child components.
   */
  public function componentFunctions() {
    // Replace 'hook_' prefix in the function declaration with a placeholder.
    $declaration = preg_replace('/(?<=function )hook/', '%module', $this->hook_info['definition']);
    return array(
      $this->name => array(
        'doxygen_first' => $this->hook_doxygen_text($this->hook_info['name']),
        'declaration'   => $declaration,
        // TODO: get the hook template from user-defined stuff.
        // TODO: how does something like hook_menu() add menu items???
        'code'          => $this->hook_info['body'],
        'has_wrapping_newlines'  => TRUE,
      ),
    );

    /*
    // Old code, adapt here:
    // See if function bodies exist; if so, use function bodies from template
    if (isset($hook['template'])) {
      // Strip out INFO: comments for advanced users
      if (!variable_get('module_builder_detail', 0)) {
        // Used to strip INFO messages out of generated file for advanced users.
        $pattern = '#\s+/\* INFO:(.*?)\*FILLERDONTCLOSECOMMENT/#ms';
        $hook['template'] = preg_replace($pattern, '', $hook['template']);
      }
      //dsm($hook);

      $function_code .= $hook['template'];
    }
    */
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

/**
 * Generator class for hook implementations for Drupal 6.
 */
class HookImplementation6 extends HookImplementation {

  /**
   * Make the doxygen first line for a given hook with the Drupal 6 format.
   *
   * @param
   *   The long hook name, eg 'hook_menu'.
   */
  function hook_doxygen_text($hook_name) {
    return "Implementation of $hook_name().";
  }

}

