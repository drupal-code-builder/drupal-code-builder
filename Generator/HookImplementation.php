<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Utility\InsertArray;

/**
 * Generator for a single hook implementation.
 *
 * This should not be requested directly; use the Hooks component instead.
 */
class HookImplementation extends PHPFunction {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $properties = parent::componentDataDefinition();

    $hook_name_property = [
      // The full name of the hook.
      'hook_name' => [
        'internal' => TRUE,
      ],
    ];
    // Insert the hook_name property before the doxygen_first property, as that
    // depends on it.
    InsertArray::insertBefore($properties, 'doxygen_first', $hook_name_property);

    $properties['doxygen_first']['default'] = function($component_data) {
      return "Implements {$component_data['hook_name']}().";
    };

    return $properties;
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
  protected function buildComponentContents($children_contents) {
    // Replace the 'hook_' part of the function declaration.
    $this->component_data['declaration'] = preg_replace('/(?<=function )hook/', '%module', $this->component_data['declaration']);

    // Replace the function body with template code if it exists.
    // TODO: 'template' is in fact always set by Hooks::getTemplates()!
    if (empty($children_contents) && isset($this->component_data['template'])) {
      $this->component_data['body'] = $this->component_data['template'];

      // The code is a single string, already indented. Tell
      // buildComponentContents() not to indent it again.
      $this->component_data['body_indent'] = 0;
    }

    return parent::buildComponentContents($children_contents);
  }

}
