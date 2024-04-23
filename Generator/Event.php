<?php

namespace DrupalCodeBuilder\Generator;

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
      'event_constant' => PropertyDefinition::create('string')
        ->setLabel('Event constant'),
      'event_value' => PropertyDefinition::create('string')
        ->setLabel('Event value'),
        // TODO we should take care of prefixing!
      'event_description' => PropertyDefinition::create('string')
        ->setLabel('Event description'),
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

    // TODO need the actual event!

    return $components;
  }

}
