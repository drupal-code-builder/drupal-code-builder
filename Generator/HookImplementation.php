<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
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
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      // The name of the file that this hook implementation should be placed into.
      'code_file' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault('%module.module'),
      'hook_name' => PropertyDefinition::create('string'),
    ]);

    $definition->getProperty('function_docblock_lines')->getDefault()
      // Expression Language lets us define arrays, which is nice.
      ->setExpression("['Implements ' ~ get('..:hook_name') ~ '().']");

    $definition->getProperty('function_name')
      ->setExpressionDefault("'%module_' ~ get('..:hook_name')");

    return $definition;
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
  public function requiredComponents(): array {
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
    $this->component_data->declaration->value = preg_replace('/(?<=function )hook/', '%module', $this->component_data->declaration->value);

    // Allow for subclasses that provide their own body code, which is not
    // indented.
    // TODO: clean this up!
    if (!empty($children_contents)) {
      $this->component_data->body_indented = FALSE;
    }

    return parent::buildComponentContents($children_contents);
  }

}
