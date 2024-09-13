<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator class for a form's builder method.
 *
 * This handles contained FormElement components to create the FormAPI array.
 */
class FormBuilder extends PHPFunction {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('declaration')
      ->setDefault(DefaultDefinition::create()
        ->setCallable([static::class, 'defaultDeclaration'])
        ->setDependencies('..:function_name')
    );

    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('form_type')
      ->setInternal(TRUE)
    );
  }

  public static function defaultDeclaration($data_item) {
    $function_name = $data_item->getParent()->function_name->value;
    return "public function {$function_name}(array £form, \Drupal\Core\Form\FormStateInterface £form_state)";

  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    // If there are no form elements, fall back to normal function generator
    // handling, which takes the body specified in the properties.
    if ($this->containedComponents->isEmpty()) {
      return parent::getContents();
    }

    $function_code = [];
    $function_code = array_merge($function_code, $this->getFunctionDocBlockLines());

    $function_code[] = $this->component_data['declaration'] . ' {';

    // The function name for a form builder is not fixed: normal forms use
    // buildForm() but entity form handlers use form().
    $body_code = [];

    $parent_call_line = "£form = parent::{$this->component_data['function_name']}(£form, £form_state);";

    if ($this->component_data->form_type->value == 'plain form') {
      $body_code[] = "// Uncomment this line if you change the base class.";
      $body_code[] = "// $parent_call_line";
    }
    else {
      $body_code[] = $parent_call_line;
    }

    $body_code[] = '';

    // Don't need to filter by type; all our child items are form elements.
    foreach ($this->containedComponents['element'] as $key => $child_item) {
      $content = $child_item->getContents();

      $form_element_key = $child_item->component_data->form_key->value;

      // TODO: move this line and the closing bracket into
      // FormElement::getContents()?
      $body_code[] = "£form['{$form_element_key}'] = [";

      // Render the FormAPI element recursively.
      $form_renderer = new \DrupalCodeBuilder\Generator\Render\FormAPIArrayRenderer($content);
      $element_lines = $form_renderer->render();
      $element_lines = $this->indentCodeLines($element_lines);
      $body_code = array_merge($body_code, $element_lines);

      $body_code[] = '];';
    }

    if ($this->component_data->form_type->value == 'plain form') {
      $body_code = array_merge($body_code, [
        '',
        "£form['submit'] = [",
        "  '#type' => 'submit',",
        "  '#value' => £this->t('Submit'),",
        "];",
      ]);
    }

    $body_code[] = '';
    $body_code[] = 'return £form;';

    $body_code = $this->indentCodeLines($body_code);

    $function_code = array_merge($function_code, $body_code);

    $function_code[] = "}";

    $function_code = array_map(function($line) {
        return str_replace('£', '$', $line);
      }, $function_code);

    return $function_code;
  }

}
