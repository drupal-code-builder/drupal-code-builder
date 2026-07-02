<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Generator\Render\DocBlock;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;
use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for a Drupal CLI command class.
 */
class CliCommand extends PHPClassFileWithInjection {

  use CommandPropertiesTrait;

  /**
   * {@inheritdoc}
   */
  protected const CLASS_DI_INTERFACE = NULL;

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('relative_namespace')
      ->setLiteralDefault('Command');

    $definition->getProperty('plain_class_name')
      ->setLabel('The short class name of the command')
      ->setInternal(TRUE)
      // Derive the class name from the command name.
      ->setCallableDefault(function ($component_data) {
        // Turn non-snake characters into snake word separators.
        $command_name = preg_replace('@-@', '_', $component_data->getParent()->command_name->value);

        return CaseString::snake($command_name)->pascal() . 'Command';
      });

    $definition->getProperty('relative_class_name')
      ->setInternal(TRUE);

    static::addCommandProperties($definition);

    // Move the injected services property lower down.
    $definition->movePropertyAfter('injected_services', 'command_options');
  }

  /**
   * {@inheritdoc}
   */
  protected function getClassAttributes(): ?PhpAttributes {
    // Prefix the command name with the module name.
    $command_name = '%module:' . $this->component_data->command_name->value;

    $attribute_data = [
      'name' => $command_name,
      'description' => $this->component_data->command_description->value,
    ];

    foreach ($this->component_data->command_name_aliases as $alias) {
      $attribute_data['aliases'][] = $alias->value;
    }

    return PhpAttributes::class(
      '\Symfony\Component\Console\Attribute\AsCommand',
      $attribute_data,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getClassDocBlock(): DocBlock {
    $docblock = DocBlock::class();
    $docblock[] = "The {$this->component_data->command_name->value} command.";

    return $docblock;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // Add the input and output parameters as a convenience.
    $parameters_data = [
      [
        'name' => 'input',
        'typehint' => '\Symfony\Component\Console\Input\InputInterface',
        'description' => "The input.",
      ],
      [
        'name' => 'output',
        'typehint' => '\Symfony\Component\Console\Output\OutputInterface',
        'description' => "The output.",
      ],
    ];

    foreach ($this->component_data->command_parameters as $parameter) {
      $parameters_data[] = [
        // TODO -- allow these to take the DataItems!?
        'name' => $parameter->name->value,
        'description' => $parameter->description->value, // "The {$parameter->value} parameter.",
        'typehint' => $parameter->type->value,
        'attribute' => [
          'class' => 'Symfony\Component\Console\Attribute\Argument',
          'data' => "The {$parameter->name->value} parameter.",
        ],
        'default_value' => $parameter->default_value->value,
      ];
    }

    foreach ($this->component_data->command_options as $option) {
      $parameters_data[] = [
        'name' => $option->name->value,
        'typehint' => $option->type->value,
        // Options always have a default value, at least NULL.
        'default_value' => $option->default_value->value ?? 'NULL',
        'description' => "The {$option->name->value} option.",
        'attribute' => [
          'class' => 'Symfony\Component\Console\Attribute\Option',
          'data' => [
            "The {$option->name->value} option.",
            'shortcut' => $option->shortcut->value,
          ],
        ],
      ];
    }

    $components['command_method'] = [
      'component_type' => 'PHPFunction',
      'function_name' => '__invoke',
      'containing_component' => '%requester',
      'prefixes' => ['public'],
      'function_docblock_lines' => [$this->component_data->command_description->value],
      'parameters' => $parameters_data,
      'return' => [
        'omit_return_tag' => TRUE,
      ],
      'use_primitive_parameter_type_declarations' => TRUE,
      'body' => [
        '$io = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);',
        'return \Symfony\Component\Console\Command\Command::SUCCESS;',
      ],
    ];

    return $components;
  }

}
