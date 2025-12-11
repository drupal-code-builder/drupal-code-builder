<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\File\DrupalExtension;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for a hooks class for D11+.
 */
class HooksClass extends Service {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('service_tag_type')->setInternal(TRUE);
    $definition->getProperty('service_name')->setInternal(TRUE);
    $definition->getProperty('decorates')->setInternal(TRUE);
    $definition->getProperty('tags')->setInternal(TRUE);

    // Move the form class name property to the top, and make it user-set rather
    // than internal with a default.
    $definition->getProperty('plain_class_name')
      ->setLabel("Hooks class name")
      ->setInternal(FALSE)
      ->setDescription("The hooks class's plain class name, e.g. \"MyHooks\".")
      ->setCallableDefault(function ($component_data) {
        // Add a suffix to the default class name based on the human-readable
        // index.
        $delta = $component_data->getParent()->getName();
        $suffix = match ($delta) {
          '0' => '',
          default => $delta + 1,
        };

        return $component_data->getParent()->root_name_pascal->value . 'Hooks' . $suffix;
      });
    $definition->getProperty('relative_namespace')
      ->setDefault(DefaultDefinition::create()
        ->setLiteral('Hook')
      );

    // The service name is the same as the class name.
    $definition->getProperty('service_name_prefix')->setLiteralDefault('');
    $definition->getProperty('service_name')->setExpressionDefault("get('..:qualified_class_name')");
    $definition->getProperty('autowire')->setLiteralDefault(TRUE);

    $definition->getProperty('class_docblock_lines')
      ->setDefault(
        DefaultDefinition::create()
          ->setLiteral(['Contains hook implementations for the %readable %base.'])
      );

    $definition->addPropertyBefore(
      'injected_services',
      MergingGeneratorDefinition::createFromGeneratorType('HookImplementationClassMethod')
        ->setName('hook_methods')
        ->setDescription('Hook implementations in this class. The same hook can be added multiple times.')
        ->setLabel('Hook implementations')
        ->setMultiple(TRUE)
        ->setProcessing(function(DataItem $component_data) {
          $component_data->containing_component = '%requester';
          $component_data->class_component_address = '..:..';
        }),
    );

    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('root_name_pascal')
      ->setInternal(TRUE)
      ->setExpressionDefault("get('..:..:..:root_name_pascal')")
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function findAdoptableComponents(DrupalExtension $extension): array {
    // For now we don't adopt hook classes, so override this method so we don't
    // return the same as the parent class.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // If there's no legacy support, this service class doesn't need to be
    // declared.
    if ($this->component_data->getItem('module:hook_implementation_type')->value == 'oo') {
      unset($components['%module.services.yml']);
    }

    return $components;
  }

}

