<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a single hook implementation.
 *
 * This should not be requested directly; use the Hooks component instead.
 */
class HookImplementation extends PHPFunction {

  /**
   * Constructor.
   *
   * @param $component_name
   *  The name of a function component should be its function (or method) name.
   * @param $component_data
   *   An array of data for the component. The following properties are
   *   required:
   *     - 'hook_name': The full name of the hook.
   *     - 'code_file': The name of the file this hook should be placed in, with
   *        tokens.
   */
  function __construct($component_name, $component_data, $root_generator) {
    // Set defaults.
    $component_data += array(
      'doxygen_first' => $this->hook_doxygen_text($component_data['hook_name']),
    );

    parent::__construct($component_name, $component_data, $root_generator);
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
  public function requiredComponents() {
    $code_file = $this->component_data['code_file'];

    return array(
      $code_file => 'ModuleCodeFile',
    );
  }

  /**
   * Return this component's parent in the component tree.
   */
  function containingComponent() {
    return $this->component_data['root_component_name'] . '/' . 'ModuleCodeFile:' . $this->component_data['code_file'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    // Replace the 'hook_' part of the function declaration.
    $this->component_data['declaration'] = preg_replace('/(?<=function )hook/', '%module', $this->component_data['declaration']);

    // Replace the function body with template code if it exists.
    if (empty($children_contents) && isset($this->component_data['template'])) {
      $this->component_data['body'] = $this->component_data['template'];

      // The code is a single string, already indented. Tell
      // buildComponentContents() not to indent it again.
      $this->component_data['body_indent'] = 0;
    }

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
