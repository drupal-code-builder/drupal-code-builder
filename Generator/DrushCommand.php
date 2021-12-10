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
        ->setLabel("The command name")
        ->setDescription("The full form of the command name, either in the format 'group:command', or just 'command' to prepend the module name as the group.")
        ->setRequired(TRUE),
      'command_name_aliases' => PropertyDefinition::create('string')
        ->setLabel("Command aliases")
        ->setDescription("Short aliases for the command.")
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
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $components = [];

    $components['commands_service'] = [
      'component_type' => 'DrushCommandsService',
      // Makes this get matched up with the data definition.
      'use_data_definition' => TRUE,
      'prefixed_service_name' => $this->component_data->root_component_name->value . '.commands',
      'plain_class_name' => CaseString::snake($this->component_data->root_component_name->value)->pascal() . 'Commands',
      'relative_namespace' => 'Commands',
      'parent_class_name' => '\Drush\Commands\DrushCommands',
      'injected_services' => $this->component_data['injected_services'],
      'docblock_first_line' => "%sentence Drush commands.",
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
