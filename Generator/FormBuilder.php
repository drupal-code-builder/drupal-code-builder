<?php

namespace DrupalCodeBuilder\Generator;

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
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->getProperty('declaration')
      ->setDefault(DefaultDefinition::create()
        ->setCallable([static::class, 'defaultDeclaration'])
        ->setDependencies('..:function_name')
    );

    return $definition;
  }

  public static function defaultDeclaration($data_item) {
    $function_name = $data_item->getParent()->function_name->value;
    return "public function {$function_name}(array £form, \Drupal\Core\Form\FormStateInterface £form_state)";

  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // If there are no form elements, fall back to normal function generator
    // handling, which takes the body specified in the properties.
    if (empty($children_contents)) {
      return parent::buildComponentContents($children_contents);
    }

    $function_code = [];
    $function_code = array_merge($function_code, $this->docBlock($this->getFunctionDocBlockLines()));

    $function_code[] = $this->component_data['declaration'] . ' {';

    // The function name for a form builder is not fixed: normal forms use
    // form() but entity form handlers use buildForm().
    $body_code = [];
    $body_code[] = "£{$this->component_data['function_name']} = parent::form(£form, £form_state);";
    $body_code[] = '';

    foreach ($children_contents as $child_item) {
      $content = $child_item['content'];

      $body_code[] = "£form['{$content['key']}'] = [";

      // Render the FormAPI element recursively.
      $form_renderer = new \DrupalCodeBuilder\Generator\Render\FormAPIArrayRenderer($content['array']);
      $element_lines = $form_renderer->render();
      $element_lines = $this->indentCodeLines($element_lines);
      $body_code = array_merge($body_code, $element_lines);

      $body_code[] = '];';
    }

    $body_code[] = '';
    $body_code[] = 'return £form;';

    $body_code = $this->indentCodeLines($body_code);

    $function_code = array_merge($function_code, $body_code);

    $function_code[] = "}";

    $function_code = array_map(function($line) {
        return str_replace('£', '$', $line);
      }, $function_code);

    return [
      'function' => [
        'role' => 'function',
        'function_name' => $this->component_data['function_name'],
        'content' => $function_code,
      ],
    ];
  }

}
