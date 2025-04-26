<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for a single procedural function hook implementation.
 */
class HookImplementationProcedural extends HookImplementationBase {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $variants = $definition->getVariants();

    foreach ($variants as $variant) {
      // The address to get the class component that holds this method.
      $variant->addProperty(PropertyDefinition::create('string')
        ->setName('class_component_address')
        ->setInternal(TRUE)
      );

      $variant->getProperty('declaration')
        ->setCallableDefault(function ($component_data) {
          $hook_info = $component_data->getParent()->hook_info->value;

          $declaration = $hook_info['definition'];

          // Run the default through processing, as that's not done
          // automatically.
          $processing = $component_data->getDefinition()->getProcessing();
          $component_data->set($declaration);
          $processing($component_data);

          return $component_data->get();
        })
        ->setProcessing(function(DataItem $component_data) {
          $short_hook_name = $component_data->getParent()->short_hook_name->get();

          // Replace the hook name from the hook info's declaration with the
          // short hook name and the module prefix.
          $declaration = preg_replace(
            '/(?<=function )hook_(\w+)/',
            '%module_' . $short_hook_name,
            $component_data->get()
          );

          $component_data->set($declaration);
        });
      }
  }

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
  public function getMergeTag() {
    // Use the short hook name, as that has tokens replaced.
    return $this->component_data->short_hook_name->value;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%self:code_file';
  }

}
