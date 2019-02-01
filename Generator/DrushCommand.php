<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * Generator for a Drush 9 command.
 */
class DrushCommand extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $data_definition += [
      'command_name' => [
        'label' => 'Command name',
        'description' => "The command name, either in the format 'group:command', or just 'command' to prepend the module name as the group.",
        'required' => TRUE,
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          if (strpos($value, ':') === FALSE) {
            return $component_data['root_component_name'] . ':' . $value;
          }
        },
      ],
      'command_name_aliases' => [
        'label' => 'Command aliases',
        'format' => 'array',
      ],
      'injected_services' => [
        'label' => 'Injected services',
        'format' => 'array',
        'options' => function(&$property_info) {
          $mb_task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');

          $options = $mb_task_handler_report_services->listServiceNamesOptions();

          return $options;
        },
        'options_extra' => \DrupalCodeBuilder\Factory::getTask('ReportServiceData')->listServiceNamesOptionsAll(),
      ],
      'command_short_class_name' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          return CaseString::snake($component_data['root_component_name'])->pascal() . 'Commands';
        },
      ],
      // TODO: move the rest of these to support multiple commands.
      'qualified_class_name_pieces' => [
        'computed' => TRUE,
        'format' => 'array',
        'default' => function($component_data) {
          $class_name_pieces = [
            'Drupal',
            $component_data['root_component_name'],
            'Commands',
            $component_data['command_short_class_name'],
          ];

          return $class_name_pieces;
        },
      ],
      'qualified_class_name' => [
        'computed' => TRUE,
        'format' => 'string',
        'default' => function($component_data) {
          return self::makeQualifiedClassName($component_data['qualified_class_name_pieces']);
        },
      ],
      'drush_service_name' => [
        'internal' => TRUE,
        'default' => function($component_data) {
          return $component_data['root_component_name'] . '.commands';
        },
      ],
    ];

    /*
    $data_definition = array(
      'command_class_name' => array(
        'label' => 'Command class name',
        'required' => TRUE,
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          $component_data['command_class_name'] = ucfirst($value);
        },
      ),
      'drush_service_name' => [
        'internal' => TRUE,
        'default' => function($component_data) {
          return $component_data['root_component_name'] . '.' . CaseString::pascal($component_data['command_class_name'])->snake();
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

    // Put the class in the 'Commands' relative namespace.
    $data_definition['relative_class_name']['default'] = function($component_data) {
      return ['Commands', $component_data['command_class_name']];
    };

    // Set the parent class.
    $data_definition['parent_class_name']['default'] = '\Drush\Commands\DrushCommands';
    */

    return $data_definition;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = [];

    $components['command_file'] = [
      'component_type' => 'PHPClassFileWithInjection',
      'relative_class_name' => [
        'Commands',
        $this->component_data['command_short_class_name'],
      ],
      'parent_class_name' => '\Drush\Commands\DrushCommands',
    ];

    $yaml_data_arguments = [];
    foreach ($this->component_data['injected_services'] as $service_id) {
      $components['service_' . $service_id] = array(
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
      );

      // Add the service ID to the arguments in the YAML data.
      $yaml_data_arguments[] = '@' . $service_id;
    }

    $yaml_service_definition = [
      'class' => self::makeQualifiedClassName($this->component_data['qualified_class_name_pieces']),
    ];
    if ($yaml_data_arguments) {
      $yaml_service_definition['arguments'] = $yaml_data_arguments;
    }

    dump($yaml_service_definition);

    /*
    services:
  config_devel.commands:
    class: \Drupal\config_devel\Commands\ConfigDevelCommands
    arguments:
      - '@module_handler'
      - '@theme_handler'
      - '@info_parser'
      - '@config.factory'
      - '@config_devel.writeback_subscriber'
      - '@config_devel.auto_import_subscriber'
      - '@file_system'
    tags:
      - { name: drush.command }
    */

    // Service tags.
    $yaml_service_definition['tags'][] = [
      'name' => 'drush.command',
    ];

    $yaml_data = [];
    $yaml_data['services'] = [
      $this->component_data['drush_service_name'] => $yaml_service_definition,
    ];

    $components['drush.services.yml'] = [
      'component_type' => 'YMLFile',
      'filename' => 'drush.services.yml',
      'yaml_data' => $yaml_data,
      'yaml_inline_level' => 3,
      // Expand the tags property further than the others.
      'inline_levels_extra' => [
        'tags' => [
          'address' => ['services', '*', 'tags'],
          'level' => 4,
        ],
      ],
    ];

    return $components;
  }

}
