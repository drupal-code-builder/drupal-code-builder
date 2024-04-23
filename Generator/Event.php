<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\PropertyListInterface;

/**
 * Generator class for TODO
 */
class Event extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'event_name' => PropertyDefinition::create('string')
        ->setLabel('Event name')
        ->setDescription('The snake case string name of the event. The module prefix is added automatically.'),
      'event_value' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(
          fn ($component_data) =>
          $component_data->getParent()->root_name->value . '.' . $component_data->getParent()->event_name->value
        ),
      'event_constant' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(
          fn ($component_data) =>
          strtoupper($component_data->getParent()->event_name->value)
        ),
      'event_class_short_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(
          fn ($component_data) =>
          CaseString::snake($component_data->getParent()->event_name->value)->pascal() . 'Event'
        ),
      'event_description' => PropertyDefinition::create('string')
        ->setLabel('Event description')
        ->setLiteralDefault('TODO: description'),
      'root_name' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
      'root_name_pascal' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
      'readable_name' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $components['event_constants_class'] = [
      'component_type' => 'PHPClassFile',
      'plain_class_name' => $this->component_data->root_name_pascal->value . 'Events',
      'relative_namespace' => 'Event',
      'class_docblock_lines' => [
        'Defines events for the ' . $this->component_data->readable_name->value . ' module.',
      ],
    ];

    $components['event_constant'] = [
      'component_type' => 'PHPConstant',
      'name' => $this->component_data->event_constant->value,
      'value' => $this->component_data->event_value->value,
      'docblock_lines' => [
        'The name of the event fired when TODO.',
        // TODO: use the tag call?
        '@Event',
        // TODO: DRY!
        '@see \Drupal\%module\Event\\' . $this->component_data->event_class_short_name->value,
      ],
      'type' => 'string',
      'containing_component' => '%requester:event_constants_class',
    ];

    $components['event_class'] = [
      'component_type' => 'PHPClassFile',
      // But wrong case!
      'plain_class_name' => $this->component_data->event_class_short_name->value,
      'relative_namespace' => 'Event',
      'parent_class_name' => '\Drupal\Component\EventDispatcher\Event',
      'class_docblock_lines' => [
        'The ' . $this->component_data->event_description->value . ' event.',
      ],
    ];

    return $components;
  }

}
