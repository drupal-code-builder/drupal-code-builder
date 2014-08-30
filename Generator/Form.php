<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\Form.
 */

namespace ModuleBuider\Generator;

/**
 * Generator class for forms.
 *
 * Fuzzy component which itself contains other components.
 */
class Form extends BaseGenerator {

  /**
   * The unique name of this generator.
   *
   * A Form generator should use as its name the form ID.
   */
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
   *      - 'form_code_bodies': (optional) An array of code bodies. Each should
   *        be in a format suitable to return from componentFunctions(). Any or
   *        all of the following keys may be present:
   *        - 'builder': The form builder.
   *        - 'validate': The validate handler.
   *        - 'submit': The submit handler.
   */
  function __construct($component_name, $component_data = array()) {
    // Set some default properties.
    $component_data += array(
      'code_file' => '%module.module',
      'form_code_bodies' => array(),
    );

    parent::__construct($component_name, $component_data);
  }

  /**
   * Return an array of subcomponent types.
   */
  protected function requiredComponents() {
    // Request the file we belong to.
    return array(
      $this->component_data['code_file'] => 'ModuleCodeFile',
    );
  }

  /**
   * Return this component's parent in the component tree.
   */
  function containingComponent() {
    return $this->component_data['code_file'];
  }

  /**
   * The name of the form.
   *
   * This allows subclasses to change this easily.
   *
   * @return
   *  The machine name of the form, i.e., the name of the form builder function.
   */
  protected function getFormName() {
    return $this->name;
  }

  /**
   * Called by ModuleCodeFile to collect functions from its child components.
   */
  public function componentFunctions() {
    $form_builder   = $this->getFormName();
    $form_validate  = $form_builder . '_validate';
    $form_submit    = $form_builder . '_submit';

    // Default code bodies for the three functions.
    $form_code_bodies = $this->component_data['form_code_bodies'] + array(
      'builder' => array(
        "£form['element] = array(",
        "  '#type' => 'textfield',",
        "  '#title' => t('Enter a value'),",
        "  '#required' => TRUE,",
        ");",
        "",
        "return £form;",
      ),
      'validate' => array(
        "if (£form_state['values']['element'] != 'hello') {",
        "  form_set_error('element', t('Please say hello?'));",
        "}",
      ),
      'submit' => '',
    );

    return array(
      // The form builder itself.
      $form_builder => array(
        'doxygen_first' => 'Form builder.',
        'declaration'   => "function $form_builder" . '($form, &$form_state)',
        'code'          => $form_code_bodies['builder'],
      ),
      // The validate handler.
      $form_validate => array(
        'doxygen_first' => 'Form validate handler.',
        'declaration'   => "function $form_validate" . '($form, &$form_state)',
        'code'          => $form_code_bodies['validate'],
      ),
      // The submit handler.
      $form_submit => array(
        'doxygen_first' => 'Form submit handler.',
        'declaration'   => "function $form_submit" . '($form, &$form_state)',
        'code'          => $form_code_bodies['submit'],
      ),
    );
  }

}
