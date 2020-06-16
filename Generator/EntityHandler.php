<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for entity handler classes.
 */
class EntityHandler extends PHPClassFile {

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition() + [
      'entity_type_id' => [
        'internal' => TRUE,
        // Means the ComponentCollector should copy in the property from the
        // requesting component.
        'acquired' => TRUE,
      ],
      'plain_class_name' => [
        'internal' => TRUE,
        'acquired' => TRUE,
      ],
      'entity_type_label' => [
        'internal' => TRUE,
        'acquired' => TRUE,
      ],
      'handler_type' => [
        'internal' => TRUE,
      ],
      'handler_label' => [
        'internal' => TRUE,
      ],
    ];

    // Note that relative_class_name is given by the entity type component.

    $data_definition['class_docblock_lines']->setDefault(DefaultDefinition::create()
      ->setExpression("['Provides the ' ~ getChildValue(parent, 'root_component_name') ~ ' handler for the ' ~ getChildValue(parent, 'entity_type_label') ~ ' entity.']"));

    return $data_definition;
  }

}
