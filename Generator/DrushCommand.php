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
      'command_method_name' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          $command_name = preg_replace('@.+:@', '', $component_data['command_name']);

          return CaseString::snake($command_name)->camel();
        },
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

    return $data_definition;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = [];

    $components['command_file'] = [
      'component_type' => 'DrushCommandFile',
      'relative_class_name' => [
        'Commands',
        $this->component_data['command_short_class_name'],
      ],
      'parent_class_name' => '\Drush\Commands\DrushCommands',
    ];

    $components['command_method'] = [
      'component_type' => 'PHPFunction',
      'containing_component' => '%requester:command_file',
      'declaration' => "public function {$this->component_data['command_method_name']}()",
      'doxygen_first' => '',
      'body' => [],
    ];

    // TODO: this won't work with multiple commands -- merge needs to be
    // handled.
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
