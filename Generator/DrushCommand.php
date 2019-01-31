<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a Drush 9 command.
 */
class DrushCommand extends PHPClassFileWithInjection {

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $data_definition = array(
      'command_class_name' => array(
        'label' => 'Form class name',
        'required' => TRUE,
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          $component_data['command_class_name'] = ucfirst($value);
        },
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

    // Put the class in the 'Commands' relative namespace.
    $data_definition['relative_class_name']['default'] = function($component_data) {
      return ['Commands', $component_data['command_class_name']];
    };

    // Set the parent class.
    $data_definition['parent_class_name']['default'] = '\Drush\Commands\DrushCommands';

    return $data_definition;
  }

}
