<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

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
      'entity_class_name' => [
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

    $data_definition['docblock_first_line']['default'] = function ($component_data) {
      return "Provides the {$component_data['handler_label']} handler for the {$component_data['entity_type_label']} entity.";
    };

    return $data_definition;
  }

}
