<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\PropertyListInterface;

/**
 * Trait providing properties for command generators.
 */
trait CommandPropertiesTrait {

  /**
   * Add command properties.
   *
   * @param \MutableTypedData\Definition\PropertyListInterface $definition
   *   The property definition.
   */
  protected static function addCommandProperties(PropertyListInterface $definition): void {
    // Taken from \Symfony\Component\Console\Attribute\Argument, which obviously
    // has no documentation.
    $types = ['string', 'bool', 'int', 'float', 'array'];
    $type_options = array_combine($types, $types);

    $definition->addProperties([
      'command_name' => PropertyDefinition::create('string')
        ->setLabel("The command name")
        ->setDescription("The command name, without the module name prefix.")
        ->setRequired(TRUE),
      'command_name_aliases' => PropertyDefinition::create('string')
        ->setLabel("Command aliases")
        ->setDescription("Short aliases for the command.")
        ->setMultiple(TRUE),
      'command_description' => PropertyDefinition::create('string')
        ->setLabel("Command description")
        ->setRequired(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setExpression("'The ' ~ get('..:command_name') ~ ' command.'")
          ->setDependencies('..:command_name')
        ),
      'command_parameters' => PropertyDefinition::create('complex')
        ->setLabel("Command parameters")
        ->setMultiple(TRUE)
          ->setProperties([
        'name' => PropertyDefinition::create('string')
          ->setLabel("Parameter name")
          ->setRequired(TRUE),
        'description' => PropertyDefinition::create('string')
          ->setLabel("Parameter description")
          ->setLiteralDefault('Parameter description.'),
        'type' => PropertyDefinition::create('string')
          ->setLabel("Type")
          ->setOptionsArray($type_options)
          ->setRequired(TRUE),
        'default_value' => PropertyDefinition::create('string')
          ->setLabel("Default value. Omit to make the parameter required.")
          ->setDescription("A string, numeric value, or one of the strings 'TRUE', 'FALSE', 'NULL' for those constants."),
        ]),
      'command_options' => PropertyDefinition::create('complex')
        ->setLabel("Command options")
        ->setMultiple(TRUE)
        ->setProperties([
          'name' => PropertyDefinition::create('string')
            ->setLabel("Option name")
            ->setRequired(TRUE),
          'description' => PropertyDefinition::create('string')
            ->setLabel("Option description")
            ->setLiteralDefault('Option description.'),
          'type' => PropertyDefinition::create('string')
            ->setLabel("Type")
            ->setOptionsArray($type_options)
            ->setRequired(TRUE),
          'default_value' => PropertyDefinition::create('string')
            ->setLabel("Default value. Omit to make the parameter required.")
            ->setDescription("A string, numeric value, or one of the strings 'TRUE', 'FALSE', 'NULL' for those constants."),
          'shortcut' => PropertyDefinition::create('string')
            ->setInternal(TRUE)
            ->setCallableDefault(fn ($component_data) => substr($component_data->getParent()->name->value, 0, 1)),
        ]),
    ]);
  }

}
