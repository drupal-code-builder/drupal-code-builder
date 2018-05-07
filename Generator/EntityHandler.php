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

    // Override some parent definitions to provide computed defaults.
    // TODO: remove, always given by entity type generator.
    $data_definition['relative_class_name']['default'] = function ($component_data) {
      return [
        'Entity',
        'Handler',
        // Class name is entity type + handler type, e.g. CatListBuilder.
        $component_data['entity_class_name'] . CaseString::snake($component_data['handler_type'])->pascal(),
      ];
    };
    $data_definition['docblock_first_line']['default'] = function ($component_data) {
      return "Provides the {$component_data['handler_label']} handler for the {$component_data['entity_type_label']} entity.";
    };

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueID() {
    // Ensure this is unique across entity types.
    return $this->component_data['root_component_name'] . '/' . $this->component_data['entity_type_id'] . ':' . $this->component_data['handler_type'];
  }

}
