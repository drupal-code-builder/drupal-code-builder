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
   * The unique name of this generator.
   *
   * A Form generator should use as its name the form ID.
   */
   // ARGH will clash with func name :()
  public $name;

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values.
   *   Valid properties are:
   *      - 'code_file': The code file to place this form in. This may contain
   *        placeholders.
   */
  function __construct($component_name, $component_data, $root_generator) {
    // Set some default properties.
    $component_data += array(
      'code_file' => '%module.module',
    );

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'form_id' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          return $this->name;
        },
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
      $this->component_data['code_file'] => 'ModuleCodeFile',
      // Request the form functions.
      $form_builder => array(
        'component_type' => 'PHPFunction',
        'code_file' => '%module.module',
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
        'body_indent' => 2,
      ),
      $form_name . '_validate' => array(
        'component_type' => 'PHPFunction',
        'code_file' => '%module.module',
        'doxygen_first' => 'Form validate handler.',
        'declaration' => "function $form_validate(£form, &£form_state)",
        'body' => array(
          "if (£form_state['values']['element'] != 'hello') {",
          "  form_set_error('element', t('Please say hello?'));",
          "}",
        ),
        'body_indent' => 2,
      ),
      $form_name . '_submit' => array(
        'component_type' => 'PHPFunction',
        'code_file' => '%module.module',
        'doxygen_first' => 'Form submit handler.',
        'declaration' => "function $form_submit(£form, &£form_state)",
        'body' => '',
        'body_indent' => 2,
      ),
    );

    return $components;
  }

}
