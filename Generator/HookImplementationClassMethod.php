<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;

/**
 * Generator for a single OO hook implementation.
 *
 * This should not be requested directly; use the Hooks component instead.
 */
class HookImplementationClassMethod extends HookImplementationBase {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'hook_method_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    return [
      'class' => [
        'component_type' => 'PHPClassFile',
        'plain_class_name' => '%PascalHooks',
        'relative_namespace' => 'Hooks',
        'class_docblock_lines' => [
          'Contains hook implementations for the %readable %base.'
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%self:class';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    // Replace the hook name from the hook info's declaration with the method
    // name.
    $this->component_data->declaration->value = preg_replace(
      '/(?<=function )hook_(\w+)/',
      $this->component_data->hook_method_name->value,
      $this->component_data->declaration->value
    );

    // Add the 'public' prefix.
    $this->component_data->declaration->value = 'public ' . $this->component_data->declaration->value;

    return parent::getContents();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFunctionAttributes(): array {
    $short_hook_name = preg_replace('/^hook_/', '', $this->component_data->hook_name->value);

    $attribute = PhpAttributes::method(
      '\Drupal\Core\Hook\Attribute\Hook',
      $short_hook_name,
    );
    return [$attribute];
  }

}
