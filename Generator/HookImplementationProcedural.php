<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a single procedural function hook implementation.
 *
 * This should not be requested directly; use the Hooks component instead.
 */
class HookImplementationProcedural extends HookImplementationBase {

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $code_file = $this->component_data['code_file'];

    $components['code_file'] = [
      'component_type' => 'ExtensionCodeFile',
      'filename' => $code_file,
    ];

    return $components;
  }

  /**
   * {@inheritdoc}
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
