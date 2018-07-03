<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for forms.
 *
 * This doesn't participate in the tree, but creates PHPFunction components
 * which do.
 */
class Form7 extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'code_file' => [
        // The code file to place the form's functions into. This may contain
        // placeholders.
        'internal' => TRUE,
        'default' => '%module.module',
      ],
      'form_id' => [
        'computed' => TRUE,
        // TODO: not yet in use.
        'default' => '',
      ],
    ];
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $form_name = $this->component_data['form_id'];
    $form_builder   = $form_name;
    $form_validate  = $form_name . '_validate';
    $form_submit    = $form_name . '_submit';

    $components = array(
      // Request the file we belong to.
      $this->component_data['code_file'] => [
        'component_type' => 'ModuleCodeFile',
        'filename' => $this->component_data['code_file'],
      ],
      // Request the form functions.
      $form_builder => array(
        'component_type' => 'PHPFunction',
        'containing_component' => '%module.module',
        'doxygen_first' => 'Form builder.',
        'declaration' => "function $form_builder(£form, &£form_state)",
        'body' => array(
          "£form['element'] = array(",
          "  '#type' => 'textfield',",
          "  '#title' => t('Enter a value'),",
          "  '#required' => TRUE,",
          ");",
          "",
          "return £form;",
        ),
      ),
      $form_name . '_validate' => array(
        'component_type' => 'PHPFunction',
        'containing_component' => '%module.module',
        'doxygen_first' => 'Form validate handler.',
        'declaration' => "function $form_validate(£form, &£form_state)",
        'body' => array(
          "if (£form_state['values']['element'] != 'hello') {",
          "  form_set_error('element', t('Please say hello?'));",
          "}",
        ),
      ),
      $form_name . '_submit' => array(
        'component_type' => 'PHPFunction',
        'containing_component' => '%module.module',
        'doxygen_first' => 'Form submit handler.',
        'declaration' => "function $form_submit(£form, &£form_state)",
        'body' => '',
      ),
    );

    return $components;
  }

}
