<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Utility\InsertArray;

/**
 * Generator for a single procedural function hook implementation.
 *
 * This should not be requested directly; use the Hooks component instead.
 */
class HookImplementationProcedural extends HookImplementationBase {

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
        'component_type' => 'ExtensionCodeFile',
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
  public function getContents(): array {
    // Replace the 'hook_' part of the function declaration.
    $this->component_data->declaration->value = preg_replace('/(?<=function )hook/', '%module', $this->component_data->declaration->value);

    return parent::getContents();
  }

}
