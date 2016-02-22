<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Form8.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator class for forms on Drupal 8.
 */
class Form8 extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  protected function requiredComponents() {
    $form_name = $this->getFormName();

    $components = array(
      // Request the form functions.
      'getFormId' => array(
        'component_type' => 'PHPFunction',
        'code_file' => $this->name,
        'doxygen_first' => '{@inheritdoc}',
        'declaration' => 'public function getFormId()',
        'body' => array(
          "return '$form_name';",
        ),
        'body_indent' => 2,
      ),
      'buildForm' => array(
        'component_type' => 'PHPFunction',
        'code_file' => $this->name,
        'doxygen_first' => 'Form constructor.',
        'declaration' => 'public function buildForm(array $form, FormStateInterface $form_state)',
        'body' => array(
          "£form['element] = array(",
          "  '#type' => 'textfield',",
          "  '#title' => t('Enter a value'),",
          "  '#required' => TRUE,",
          ");",
          "",
          "return £form;",
        ),
        'body_indent' => 2,
      ),
      'submitForm' => array(
        'component_type' => 'PHPFunction',
        'code_file' => $this->name,
        'doxygen_first' => 'Form submission handler.',
        'declaration' => 'public function submitForm(array &$form, FormStateInterface $form_state)',
        'body' => '',
        'body_indent' => 2,
      ),
    );

    return $components;
  }

}
