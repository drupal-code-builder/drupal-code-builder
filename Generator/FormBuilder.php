<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for a form's builder method.
 *
 * This handles contained FormElement components to create the FormAPI array.
 *
 * TODO: to make this usable on D7, need to get rid of PHPMethod.
 */
class FormBuilder extends PHPMethod {

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // If there are no form elements, fall back to normal function generator
    // handling, which takes the body specified in the properties.
    if (empty($children_contents)) {
      return parent::buildComponentContents($children_contents);
    }

    $function_code = array();
    $function_code = array_merge($function_code, $this->docBlock($this->component_data['doxygen_first']));

    $function_code[] = $this->component_data['declaration'] . ' {';

    foreach ($children_contents as $child_item) {
      $content = $child_item['content'];

      $function_code[] = "  £form['{$content['key']}'] = [";

      foreach ($content['array'] as $attribute_name => $form_attribute_value) {
        $function_code[] = "    '{$attribute_name}' => {$form_attribute_value},";
      }

      $function_code[] = '  ];';
    }

    $function_code[] = '';
    $function_code[] = '  return £form;';

    $function_code[] = "}";

    $function_code = array_map(function($line) {
        return str_replace('£', '$', $line);
      }, $function_code);

    return [
      'function' => [
        'role' => 'function',
        'content' => $function_code,
      ],
    ];
  }

}
