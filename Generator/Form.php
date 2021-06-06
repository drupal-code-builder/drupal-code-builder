<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator class for forms on Drupal 8.
 *
 * Note that entity forms use the EntityForm generator which does *not*
 * inherit from this class!
 */
class Form extends PHPClassFileWithInjection {

  protected $hasStaticFactoryMethod = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $parent_data_definition = parent::componentDataDefinition();

    $data_definition = array(
      // Move the form class name property to the top, and override its default.
      'plain_class_name' => $parent_data_definition['plain_class_name']
        ->setLabel("Form class name.")
        ->setDescription("The form class's plain class name, e.g. \"MyForm\"."),
      'form_id' => PropertyDefinition::create('string')
        ->setLabel('The form ID.')
        ->setInternal(TRUE)
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("get('..:root_component_name') ~ '_' ~ machineFromPlainClassName(get('..:plain_class_name'))")
            ->setDependencies('..:root_component_name', '..:plain_class_name')
        ),
      'injected_services' => PropertyDefinition::create('string')
        ->setLabel('Injected services')
        ->setDescription("Services to inject. Additionally, use 'storage:TYPE' to inject entity storage handlers.")
        ->setMultiple(TRUE)
        ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportServiceData')),
      'form_elements' => [
        // Internal for now. TODO: expose to the UI.
        'internal' => TRUE,
        'format' => 'compound',
        'component_type' => 'FormElement',
      ],
    );

    // Put the rest of the parent definitions after ours.
    $data_definition += $parent_data_definition;

    // Put the class in the 'Form' relative namespace.
    $data_definition['relative_namespace']
      ->setLiteralDefault('Form');

    $data_definition['plain_class_name']
      ->setLiteralDefault('MyForm');

    $data_definition['relative_class_name']->setInternal(TRUE);

    // Set the parent class.
    $data_definition['parent_class_name']
      ->setLiteralDefault('\Drupal\Core\Form\FormBase');

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $form_name = $this->component_data['form_id'];

    $components = array(
      // Request the form functions.
      'getFormId' => array(
        'component_type' => 'PHPFunction',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function getFormId()',
        'body' => array(
          "return '$form_name';",
        ),
      ),
      'buildForm' => array(
        'component_type' => 'FormBuilder',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'function_name' => 'buildForm',
        'body' => array(
          "// Uncomment this line if you change the base class.",
          "// £form = parent::buildForm(£form, £form_state);",
          "",
          "£form['element'] = [",
          "  '#type' => 'textfield',",
          "  '#title' => £this->t('Enter a value'),",
          "  '#description' => £this->t('Enter a description'),",
          "  '#default_value' => 'enter the default value',",
          "  '#required' => TRUE,",
          "];",
          "",
          "£form['submit'] = [",
          "  '#type' => 'submit',",
          "  '#value' => £this->t('Submit'),",
          "];",
          "",
          "return £form;",
        ),
      ),
      'validateForm' => [
        'component_type' => 'PHPFunction',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => '',
      ],
      'submitForm' => array(
        'component_type' => 'PHPFunction',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => '',
      ),
    );

    foreach ($this->component_data['injected_services'] as $service_id) {
      $components['service_' . $service_id] = array(
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
      );
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // TEMPORARY, until Generate task handles returned contents.
    $this->functions = $this->filterComponentContentsForRole($children_contents, 'function');
    $this->injectedServices = $this->filterComponentContentsForRole($children_contents, 'service');
    $this->childContentsGrouped = $this->groupComponentContentsByRole($children_contents);

    return array();
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    $this->collectSectionBlocksForDependencyInjection();
  }

}
