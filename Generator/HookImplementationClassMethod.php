<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Attribute\DrupalCoreVersion;
use DrupalCodeBuilder\Attribute\RelatedBaseClass;
use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;
use DrupalCodeBuilder\Utility\InsertArray;

/**
 * Generator for a single hook implementation.
 *
 * This should not be requested directly; use the Hooks component instead.
 *
 * This is NOT the Drupal 11 version of HookImplementationProcedural, because on
 * Drupal 11 we support both styles of hook implementation, controlled with a
 * setting on the Module component.
 *
 * TODO: For Drupal 12, this class WILL get declared as a versioned
 * HookImplementation.
 */
class HookImplementationClassMethod extends HookImplementationBase {

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $code_file = $this->component_data['code_file'];

    return [
      'class' => [
        'component_type' => 'PHPClassFile',
        // TODO: prefix with pascal name!
        // $this->component_data->root_name_pascal->value
        // but need to acquire AARRRGH
        'plain_class_name' => 'Hooks',
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
    // Add the 'public' prefix.
    $this->component_data->declaration->value = 'public ' . $this->component_data->declaration->value;

    return parent::getContents();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFunctionAttributes(): ?PhpAttributes {
    $attribute = PhpAttributes::method(
      '\Drupal\Core\Hook\Attribute\Hook',
      $this->component_data['hook_name'],
    );
    return $attribute;
  }

}
