<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * Generator class for entity form handlers.
 *
 * Extend from EntityHandler rather than Form, as core's base EntityForm class
 * does a lot of the form work.
 */
class EntityForm extends EntityHandler {

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = array(
      // Request the form functions.
      // Note that for entity forms, buildForm() shouldn't be used, but form()
      // instead. (DrupalWTF!)
      'form' => array(
        'component_type' => 'FormBuilder',
        'containing_component' => '%requester',
        'doxygen_first' => 'Form constructor.',
        'declaration' => 'public function form(array $form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => [],
      ),
      'submitForm' => array(
        'component_type' => 'PHPMethod',
        'containing_component' => '%requester',
        'doxygen_first' => 'Form submission handler.',
        'declaration' => 'public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => [],
      ),
    );

    return $components;
  }

}
