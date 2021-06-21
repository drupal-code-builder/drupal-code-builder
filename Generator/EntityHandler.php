<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for entity handler classes.
 */
class EntityHandler extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'entity_type_id' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
      'plain_class_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      'entity_type_label' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
      'handler_type' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      'handler_label' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
    ]);

    // Note that relative_class_name is given by the entity type component.

    $definition->getProperty('class_docblock_lines')->setDefault(DefaultDefinition::create()
      ->setExpression("['Provides the ' ~ get('..:handler_label') ~ ' handler for the ' ~ get('..:entity_type_label') ~ ' entity.']"));

    return $definition;
  }

}
