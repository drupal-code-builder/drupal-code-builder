<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Form.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for forms on Drupal 8.
 */
class Form extends PHPClassFileWithInjection {

  protected $hasStaticFactoryMethod = TRUE;

  /**
   * An array of data about injected services.
   */
  protected $injectedServices = [];

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values.
   */
  function __construct($component_name, $component_data, $root_generator) {
    // Set some default properties.
    $component_data += array(
      'injected_services' => [],
      // TODO: should be in default value callback.
      'form_id' => $component_data['root_component_name'] . '_' . strtolower($component_data['form_class_name']),
    );

    // TODO: this should be done in a property processing callback.
    $component_data['form_class_name'] = ucfirst($component_data['form_class_name']);

    //ddpr($component_data);

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * Return a unique ID for this component.
   *
   * @return
   *  The unique ID
   */
  public function getUniqueID() {
    return $this->component_data['root_component_name'] . '/' . $this->type . ':' . $this->component_data['form_id'];
  }

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $data_definition = array(
      'form_class_name' => array(
        'label' => 'Form class name',
        'required' => TRUE,
      ),
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

    // Take the class name from the service name.
    $data_definition['relative_class_name']['default'] = function($component_data) {
      return ['Form', $component_data['form_class_name']];
    };

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
        'code_file_id' => $this->getUniqueId(),
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
        'code_file_id' => $this->getUniqueId(),
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
        'code_file_id' => $this->getUniqueId(),
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
   * Produces the class declaration.
   */
  function class_declaration() {
    $this->component_data['parent_class_name'] = '\Drupal\Core\Form\FormBase';

    return parent::class_declaration();
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    $this->collectSectionBlocksForDependencyInjection();
  }

}
