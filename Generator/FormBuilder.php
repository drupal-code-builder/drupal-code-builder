<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for a form's builder method.
 *
 * This handles contained FormElement components to create the FormAPI array.
 */
class FormBuilder extends PHPFunction {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $data_definition['declaration']['process_default'] = TRUE;
    $data_definition['declaration']['default'] = function($component_data) {
      // TODO: different for Drupal 7: no 'public' (as function, not method)
      // and no class typehint for the $form_state.
      return "public function {$component_data['function_name']}(array £form, \Drupal\Core\Form\FormStateInterface £form_state)";
    };

    return $data_definition;
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

    $function_code = array();
    $function_code = array_merge($function_code, $this->docBlock($this->component_data['doxygen_first']));

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
        'content' => $function_code,
      ],
    ];
  }

}
