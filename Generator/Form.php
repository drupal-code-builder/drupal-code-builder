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
  public function getUniqueID() {
    return $this->component_data['root_component_name'] . '/' . $this->type . ':' . $this->component_data['form_id'];
  }

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
        'component_type' => 'PHPMethod',
        'code_file' => $this->name,
        'containing_component' => '%requester',
        'doxygen_first' => '{@inheritdoc}',
        'declaration' => 'public function getFormId()',
        'body' => array(
          "return '$form_name';",
        ),
        'body_indent' => 2,
      ),
      'buildForm' => array(
        'component_type' => 'PHPMethod',
        'code_file' => $this->name,
        'containing_component' => '%requester',
        'doxygen_first' => 'Form constructor.',
        'declaration' => 'public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)',
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
      'submitForm' => array(
        'component_type' => 'PHPMethod',
        'code_file' => $this->name,
        'containing_component' => '%requester',
        'doxygen_first' => 'Form submission handler.',
        'declaration' => 'public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => '',
        'body_indent' => 2,
      ),
    );

    foreach ($this->component_data['injected_services'] as $service_id) {
      $components[$this->name . '_' . $service_id] = array(
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
  function buildComponentContents($children_contents) {
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
