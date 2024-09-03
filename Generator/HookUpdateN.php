<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\File\DrupalExtension;

/**
 * Generator for hook_update_N() implementation.
 */
class HookUpdateN extends HookImplementationProcedural {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    // The next schema number to use for the hook implementation.
    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('schema_number')
      ->setInternal(TRUE)
      ->setCallableDefault(function ($component_data) {
        // Get overwritten by detectExistence() if existing implementations are
        // found.
        return (\DrupalCodeBuilder\Factory::getEnvironment()->getCoreMajorVersion() * 1000) + 1;
      })
    );
  }

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

    if ($hook_update_schema_numbers) {
      $this->component_data->schema_number->value = max($hook_update_schema_numbers) + 1;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    // Replace the '_N' part of the function declaration.
    $this->component_data->declaration->value = preg_replace('/(?<=hook_update_)N/', $this->component_data->schema_number->value, $this->component_data->declaration->value);
    // Also do the function name.
    $this->component_data->function_name->value = preg_replace('/(?<=update_)N/', $this->component_data->schema_number->value, $this->component_data->function_name->value);

    return parent::getContents();
  }

}
