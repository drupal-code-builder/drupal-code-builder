<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for a Drush 9 command.
 */
class DrushCommand extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'command_name' => PropertyDefinition::create('string')
        ->setLabel("The command name, either in the format 'group:command', or just 'command' to prepend the module name as the group.")
        ->setRequired(TRUE),
      'command_name_aliases' => PropertyDefinition::create('string')
        ->setLabel("Command aliases")
        ->setMultiple(TRUE),
      'command_description' => PropertyDefinition::create('string')
       ->setLabel("Command description."),
      'command_method_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(function ($component_data) {
          $command_name = preg_replace('@.+:@', '', $component_data->getParent()->command_name->value);

          return CaseString::snake($command_name)->camel();
        }),
      'injected_services' => PropertyDefinition::create('string')
        ->setLabel('Injected services')
        ->setDescription("Services to inject. Additionally, use 'storage:TYPE' to inject entity storage handlers.")
        ->setMultiple(TRUE)
        ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportServiceData')),
      // Experimental. Define the data here that will then be set by
      // self::requiredComponents(). This is mostly needed so that the Service
      // generator has access to the whole data, because it expects to be able
      // to access module generator configuration options.
      'commands_service' => static::getLazyDataDefinitionForGeneratorType('DrushCommandsService')
        ->setInternal(TRUE),

    ]);

    return $definition;
  }


  /**
   * Define the component data this component needs to function.
   */
  public static function XXXcomponentDataDefinition() {
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
      'command_description' => [
        'label' => 'Command description',
        'required' => TRUE,
        'default' => 'TODO: write a description',
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

    $components['commands_service'] = [
      'component_type' => 'DrushCommandsService',
      // Makes this get matched up with the data definition.
      'use_data_definition' => TRUE,
      'prefixed_service_name' => $this->component_data->root_component_name->value . '.commands',
      'plain_class_name' => CaseString::snake($this->component_data->root_component_name->value)->pascal() . 'Commands',
      'relative_namespace' => 'Commands',
      'injected_services' => [],
      'docblock_first_line' => "MODULE NAME Drush commands.",
    ];

    $docblock_lines = [
      $this->component_data['command_description'],
      "@command {$this->component_data['command_name']}",
      "@usage drush {$this->component_data['command_name']}",
      "  {$this->component_data['command_description']}",
    ];
    if (!empty($this->component_data['command_name_aliases'])) {
      $docblock_lines[] =  "@aliases " . implode(',', $this->component_data['command_name_aliases']);
    }

    $components['command_method'] = [
      'component_type' => 'PHPFunction',
      'containing_component' => '%requester:commands_service',
      'declaration' => "public function {$this->component_data['command_method_name']}()",
      'function_docblock_lines' => $docblock_lines,
      'body' => [],
    ];

    return $components;
  }

}
