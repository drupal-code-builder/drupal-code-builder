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
#[DrupalCoreVersion(10)]
#[DrupalCoreVersion(9)]
#[DrupalCoreVersion(8)]
#[DrupalCoreVersion(7)]
#[RelatedBaseClass('HookImplementation')]
class HookImplementationProcedural extends PHPFunction {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      // The name of the file that this hook implementation should be placed into.
      'code_file' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault('%module.module'),
      // The long hook name.
      'hook_name' => PropertyDefinition::create('string'),
    ]);

    $definition->getProperty('function_docblock_lines')->getDefault()
      // Expression Language lets us define arrays, which is nice.
      ->setExpression("['Implements ' ~ get('..:hook_name') ~ '().']");

    $definition->getProperty('function_name')
      ->setCallableDefault(function ($component_data) {
        $long_hook_name = $component_data->getParent()->hook_name->value;
        $short_hook_name = preg_replace('@^hook_@', '', $long_hook_name);
        $function_name = '%module_' . $short_hook_name;
        return $function_name;
      });

    // Hook bodies are just sample code from the code documentation, so if
    // there are contained components, these should override the sample code.
    $definition->getProperty('body_overriden_by_contained')
      ->setLiteralDefault(TRUE);
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

    // Allow for subclasses that provide their own body code, which is not
    // indented.
    // TODO: clean this up!
    if (!$this->containedComponents->isEmpty()) {
      $this->component_data->body_indented = FALSE;
    }

    return parent::getContents();
  }

}
