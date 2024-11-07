<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Abstract base class for hook implementations.
 */
abstract class HookImplementationBase extends PHPFunction {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      // The name of the file that this hook implementation should be placed
      // into.
      // For HookImplementationClassMethod this is unused, but simpler to have
      // this here rather than have Hooks decide whether to set it or not. Plus
      // we might use it at some point to decide which class to use.
      'code_file' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault('%module.module'),
      // The long hook name.
      'hook_name' => PropertyDefinition::create('string'),
      // The first docblock line from the hook's api.php definition.
      'description' => PropertyDefinition::create('string'),
    ]);

    $definition->getProperty('function_docblock_lines')->getDefault()
      // Expression Language lets us define arrays, which is nice.
      ->setExpression("['Implements ' ~ get('..:hook_name') ~ '().']");

    // This appears to be necessary even though it's not used. WTF!
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

    // Hook implementations have no @return documentation.
    $definition->getProperty('return')->getProperty('omit_return_tag')
      ->setLiteralDefault(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return $this->component_data['hook_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    // Allow for subclasses that provide their own body code, which is not
    // indented.
    // TODO: clean this up!
    if (!$this->containedComponents->isEmpty()) {
      $this->component_data->body_indented = FALSE;
    }

    return parent::getContents();
  }

}
