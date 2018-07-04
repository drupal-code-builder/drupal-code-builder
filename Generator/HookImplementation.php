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

    // The name of the file that this hook implementation should be placed into.
    $properties['code_file'] = [
      'internal' => TRUE,
      'default' => '%module.module',
    ];

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

    // Indicates that the body includes the first and closing newlines. This is
    // because the hook sample code we get from code analysis have these, but
    // it's a pain to put them in ourselves when providing hook body code.
    $properties['has_wrapping_newlines'] = [
      'format' => 'boolean',
      'internal' => TRUE,
      'default' => TRUE,
    ];

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return $this->component_data['hook_name'];
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

    return [
      'code_file' => [
        'component_type' => 'ModuleCodeFile',
        'filename' => $code_file,
      ],
    ];
  }

  /**
   * Return this component's parent in the component tree.
   */
  function containingComponent() {
    return '%self:code_file';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // Replace the 'hook_' part of the function declaration.
    $this->component_data['declaration'] = preg_replace('/(?<=function )hook/', '%module', $this->component_data['declaration']);

    // Allow for subclasses that provide their own body code, which is not
    // indented.
    // TODO: clean this up!
    if (!empty($children_contents)) {
      $this->component_data['body_indented'] = FALSE;
    }

    return parent::buildComponentContents($children_contents);
  }

}
