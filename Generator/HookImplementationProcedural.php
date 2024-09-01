<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Utility\InsertArray;
use DrupalCodeBuilder\Attribute\DrupalCoreVersion;
use DrupalCodeBuilder\Attribute\RelatedBaseClass;

/**
 * Generator for a single hook implementation.
 *
 * This should not be requested directly; use the Hooks component instead.
 */
#[DrupalCoreVersion(11)]
#[DrupalCoreVersion(10)]
#[DrupalCoreVersion(9)]
#[DrupalCoreVersion(8)]
#[DrupalCoreVersion(7)]
#[RelatedBaseClass('HookImplementation')]
class HookImplementationProcedural extends HookImplementationBase {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('function_docblock_lines')->getDefault()
      // Expression Language lets us define arrays, which is nice.
      ->setExpression("['Implements ' ~ get('..:hook_name') ~ '().']");
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

}
