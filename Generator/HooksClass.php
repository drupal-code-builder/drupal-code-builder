<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
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
      ->removeDefault();
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

