<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Attribute\DrupalCoreVersion;
use DrupalCodeBuilder\Attribute\RelatedBaseClass;
use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;
use DrupalCodeBuilder\Utility\InsertArray;

/**
 * Generator for a single OO hook implementation.
 *
 * This should not be requested directly; use the Hooks component instead.
 *
 * TODO REWRITE This is NOT the Drupal 11 version of HookImplementationProcedural, because on
 * Drupal 11 we support both styles of hook implementation, controlled with a
 * setting on the Module component.
 *
 * TODO: For Drupal 12, this class might get declared as a versioned
 * HookImplementation -- need to figure out install hooks, which remain
 * procedural.
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
    $code_file = $this->component_data->code_file->value;

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
    // Replace the hook name from the hook info's declaration with the method name.
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
  protected function getFunctionAttributes(): ?PhpAttributes {
    $short_hook_name = preg_replace('/^hook_/', '', $this->component_data->hook_name->value);

    $attribute = PhpAttributes::method(
      '\Drupal\Core\Hook\Attribute\Hook',
      $short_hook_name,
    );
    return $attribute;
  }

}
