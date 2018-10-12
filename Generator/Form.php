<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * Generator class for forms on Drupal 8.
 */
class Form extends PHPClassFileWithInjection {

  protected $hasStaticFactoryMethod = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = array(
      'form_class_name' => array(
        'label' => 'Form class name',
        'required' => TRUE,
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          $component_data['form_class_name'] = ucfirst($value);
        },
      ),
      'form_id' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          return
            $component_data['root_component_name']
            . '_'
            . CaseString::pascal($component_data['form_class_name'])->snake();
        },
      ],
      'injected_services' => array(
        'label' => 'Injected services',
        'format' => 'array',
        'options' => function(&$property_info) {
          $mb_task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');

          $options = $mb_task_handler_report_services->listServiceNamesOptions();

          return $options;
        },
        'options_extra' => \DrupalCodeBuilder\Factory::getTask('ReportServiceData')->listServiceNamesOptionsAll(),
      ),
      'form_elements' => [
        // Internal for now. TODO: expose to the UI.
        'internal' => TRUE,
        'format' => 'compound',
        'component_type' => 'FormElement',
      ],
    );

    // Put the parent definitions after ours.
    $data_definition += parent::componentDataDefinition();

    // Put the class in the 'Form' relative namespace.
    $data_definition['relative_class_name']['default'] = function($component_data) {
      return ['Form', $component_data['form_class_name']];
    };

    // Set the parent class.
    $data_definition['parent_class_name']['default'] = '\Drupal\Core\Form\FormBase';

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
        'doxygen_first' => '{@inheritdoc}',
        'declaration' => 'public function getFormId()',
        'body' => array(
          "return '$form_name';",
        ),
      ),
      'buildForm' => array(
        'component_type' => 'FormBuilder',
        'containing_component' => '%requester',
        'doxygen_first' => 'Form constructor.',
        'function_name' => 'buildForm',
        'body' => array(
          "// Uncomment this line if you change the base class.",
          "// £form = parent::buildForm(£form, £form_state);",
          "",
          "£form['element'] = [",
          "  '#type' => 'textfield',",
          "  '#title' => t('Enter a value'),",
          "  '#required' => TRUE,",
          "];",
          "",
          "return £form;",
        ),
      ),
      'submitForm' => array(
        'component_type' => 'PHPFunction',
        'containing_component' => '%requester',
        'doxygen_first' => 'Form submission handler.',
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
