<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\File\DrupalExtension;

/**
 * Generator for hook_update_N() implementation.
 */
class HookUpdateN extends HookImplementation {

  /**
   * The next schema number to use for the hook implementation.
   *
   * @var int
   */
  protected $nextSchemaNumber;

  /**
   * {@inheritdoc}
   */
  public function detectExistence(DrupalExtension $extension) {
    // This does not strictly speaking detect existence, but detects existing
    // implementations of this hook, so that our new implementation gets the
    // correct number.
    $install_filename = (
      $this->component_data->component_base_path->value ?
      $this->component_data->component_base_path->value . '/' :
      ''
      )
      . '%module.install';
    $install_filename = str_replace('%module', $this->component_data->root_component_name->value, $install_filename);

    if (!$extension->hasFile($install_filename)) {
      return;
    }

    $ast = $extension->getFileAST($install_filename);
    $install_function_nodes = $extension->getASTFunctions($ast);

    // Detect any existing implementations.
    $hook_update_schema_numbers = [];
    foreach ($install_function_nodes as $function_node) {
      $existing_function_name = (string) $function_node->name;

      $matches = [];
      if (preg_match('@' . $this->component_data['root_component_name'] . '_update_(\d+)$@', $existing_function_name, $matches)) {
        $hook_update_schema_numbers[] = $matches[1];
      }
    }

    $this->nextSchemaNumber = $hook_update_schema_numbers
      ? max($hook_update_schema_numbers) + 1
      // TODO: add test coverage for this case.
      : (\DrupalCodeBuilder\Factory::getEnvironment()->getCoreMajorVersion() * 1000) + 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    // Replace the '_N' part of the function declaration.
    $this->component_data->declaration->value = preg_replace('/(?<=hook_update_)N/', $this->nextSchemaNumber, $this->component_data->declaration->value);
    // Also do the function name.
    $this->component_data->function_name->value = preg_replace('/(?<=update_)N/', $this->nextSchemaNumber, $this->component_data->function_name->value);

    return parent::getContents();
  }

}
