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
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition() + [
      // The entity link template that the form save() method redirects to.
      'redirect_link_template' => [
        'internal' => TRUE,
      ],
    ];
    return $data_definition;
  }

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
        'docblock_inherit' => TRUE,
        'function_name' => 'form',
        'body' => [
          '$form = parent::form($form, $form_state);',
          'return $form;',
        ],
      ),
      'submitForm' => array(
        'component_type' => 'PHPFunction',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => [
          'parent::submitForm($form, $form_state);'
        ],
      ),
      'save' => [
        'component_type' => 'PHPFunction',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function save(array $form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => [
          '$saved = parent::save($form, $form_state);',
          "£form_state->setRedirectUrl(£this->entity->toUrl('{$this->component_data['redirect_link_template']}'));",
          '',
          'return $saved;',
        ],
      ],
    );

    return $components;
  }

}
