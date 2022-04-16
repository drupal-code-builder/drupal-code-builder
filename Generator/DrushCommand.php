<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PresetDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\OptionDefinition;

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
        ->setLabel("Command description"),
      'command_parameters' => PropertyDefinition::create('string')
        ->setLabel("Command parameter names")
        ->setMultiple(TRUE),
      'command_options' => PropertyDefinition::create('string')
        ->setLabel("Command options")
        ->setDescription("Enter each option as 'option_name: default', where for the default, a plain string will be quoted, a numeric is left as numeric, and string in ALL_CAPS are taken to be constants, including 'NULL', 'TRUE', 'FALSE'.")
        ->setMultiple(TRUE),
      'command_method_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(function ($component_data) {
          $command_name = preg_replace('@.+:@', '', $component_data->getParent()->command_name->value);

          return CaseString::snake($command_name)->camel();
        }),
      'inflected_injection' => PropertyDefinition::create('string')
        ->setLabel("Inflection interfaces")
        ->setMultiple(TRUE)
        ->setPresets(
          PresetDefinition::create(
            'autoloader',
            'AutoloaderAwareInterface',
            "Provides access to the class loader."
          )
          ->setForceValues([
            'service_interfaces' => [
              'value' => '\Drush\Boot\AutoloaderAwareInterface',
            ],
            'service_traits' => [
              'value' => '\Drush\Boot\AutoloaderAwareTrait',
            ],
          ]),
          PresetDefinition::create(
            'site_alias',
            'SiteAliasManagerAwareInterface',
            "The site alias manager allows alias records to be obtained."
          )
          ->setForceValues([
            'service_interfaces' => [
              'value' => '\Drush\SiteAlias\SiteAliasManagerAwareInterface',
            ],
            'service_traits' => [
              'value' => '\Consolidation\SiteAlias\SiteAliasManagerAwareTrait',
            ],
          ]),
          PresetDefinition::create(
            'custom_event',
            'CustomEventAwareInterface',
            "Allows command files to define and fire custom events that other command files can hook."
          )
          ->setForceValues([
            'service_interfaces' => [
              'value' => '\Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface',
            ],
            'service_traits' => [
              'value' => '\Consolidation\AnnotatedCommand\Events\CustomEventAwareTrait',
            ],
          ]),
          PresetDefinition::create(
            'container',
            'ContainerAwareInterface',
            "Provides Drush's dependency injection container."
          )
          ->setForceValues([
            'service_interfaces' => [
              'value' => '\League\Container\ContainerAwareInterface',
            ],
            'service_traits' => [
              'value' => '\League\Container\ContainerAwareTrait',
            ],
          ]),
        ),
      'service_interfaces' => PropertyDefinition::create('string')
        ->setMultiple(TRUE)
        ->setInternal(TRUE),
      'service_traits' => PropertyDefinition::create('string')
        ->setMultiple(TRUE)
        ->setInternal(TRUE),
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
      'interfaces' => $this->component_data->service_interfaces->values(),
      'traits' => $this->component_data->service_traits->values(),
    ];

    // Prefix the module name as the command's group if no group set already.
    if (strpos($this->component_data->command_name->value, ':') === FALSE) {
      $this->component_data->command_name = $this->component_data->root_component_name->value . ':' . $this->component_data->command_name->value;
    }

    $docblock_lines = [
      $this->component_data['command_description'],
    ];

    $usage_line = "@usage drush {$this->component_data['command_name']}";

    $parameters_data = [];
    foreach ($this->component_data->command_parameters as $parameter) {
      $parameters_data[] = [
        // TODO -- allow these to take the DataItems!?
        'name' => $parameter->value,
        // 'description' => "The {$parameter->value} parameter.",
      ];

      // Add the parameters to the @usage tag.
      $usage_line .= ' ' . $parameter->value;
    }

    $doxygen_tag_lines = [];

    // Put the @option tags first in the tag lines, so they come after the
    // @param lines that PHPFunction generator will put in.
    foreach ($this->component_data->command_options as $option) {
      list($option_name, $option_default) = explode(':', $option->value);
      $option_name = trim($option_name);
      $option_default = trim($option_default);
      if (is_numeric($option_default)) {
        $option_type = 'int';
      }
      elseif (in_array($option_default, ['TRUE', 'FALSE'])) {
        $option_type = 'bool';
      }
      elseif (preg_match('@^[[:upper:]]+$@', $option_default)) {
        // Assume that a default value that's in all CAPS and not a boolean is
        // a constant, and assume that defined constants are usually ints.
        $option_type = 'int';
      }
      else {
        $option_type = 'string';

        // Quote a string variable.
        $option_default = "'" . $option_default . "'";
      }

      // Each option is documented as a @param, but also as an @option.
      $parameters_data[] = [
        'name' => $option_name,
        'type' => $option_type,
        'default_value' => $option_default,
        'description' => "The {$option_name} option.",
      ];

      $doxygen_tag_lines[] = "@option {$option_name} Option description.";

      // Add the options to the @usage tag.
      $usage_line .= ' --' . $option_name;
    }

    $doxygen_tag_lines[] = "@command {$this->component_data['command_name']}";
    $doxygen_tag_lines[] = $usage_line;
    $doxygen_tag_lines[] = "  {$this->component_data['command_description']}";

    if (!empty($this->component_data['command_name_aliases'])) {
      $doxygen_tag_lines[] =  "@aliases " . implode(',', $this->component_data['command_name_aliases']);
    }

    $components['command_method'] = [
      'component_type' => 'PHPFunction',
      'function_name' => $this->component_data['command_method_name'],
      'containing_component' => '%requester:commands_service',
      'declaration' => "public function {$this->component_data['command_method_name']}()",
      'function_docblock_lines' => $docblock_lines,
      'doxygen_tag_lines' => $doxygen_tag_lines,
      'parameters' => $parameters_data,
      'body' => [],
    ];

    return $components;
  }

}
