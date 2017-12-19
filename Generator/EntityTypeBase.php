<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\AnnotationTrait;
use CaseConverter\CaseString;

/**
 * Base generator entity types.
 */
abstract class EntityTypeBase extends PHPClassFile {

  use NameFormattingTrait;

  use AnnotationTrait;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = [
      'entity_type_id' => [
        'label' => 'Entity type ID',
        'description' => "The identifier of the entity type.",
        'required' => TRUE,
        // TODO: validation? static::ID_MAX_LENGTH
      ],
      'entity_type_label' => [
        'label' => 'Entity type label',
        'description' => "The human-readable label for the entity type.",
        'process_default' => TRUE,
        'default' => function($component_data) {
          $entity_type_id = $component_data['entity_type_id'];

          // Convert the entity type to camel case. E.g., 'my_entity_type'
          //  becomes 'My Entity Type'.
          return self::snakeToTitle($entity_type_id);
        },
      ],
      'entity_class_name' => [
        'label' => 'Entity class name',
        'description' => "The short class name of the entity.",
        'process_default' => TRUE,
        'default' => function($component_data) {
          $entity_type_id = $component_data['entity_type_id'];
          return CaseString::snake($entity_type_id)->pascal();
        },
      ],
      'interface_parents' => [
        'label' => 'Interface parents',
        'description' => "The interfaces the entity interface inherits from.",
        'format' => 'array',
        // TODO should an array format property always get an empty array as a
        // minimum in process?
        'process_default' => TRUE,
        'default' => [],
        // Data for the options and processing callbacks.
        // Bit of a hack, as we need full class names internally, but they're
        // unwieldy in the UI.
        '_long_options' => static::interfaceParents(),
        'options' => function(&$property_info) {
          // Trim the namespace off the options, for UIs that show the keys,
          // such as Drush.
          $options = [];
          foreach ($property_info['_long_options'] as $key => $text) {
            $short_class_name = substr(strrchr($key, "\\"), 1);
            $options[$short_class_name] = $text;
          }
          return $options;
        },
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          $lookup = [];
          foreach ($property_info['_long_options'] as $qualified_class_name => $text) {
            $short_class_name = substr(strrchr($qualified_class_name, "\\"), 1);
            $lookup[$qualified_class_name] = $short_class_name;
          }
          // TODO: DRY: prevent repetition of property name!
          $component_data['interface_parents'] = array_keys(array_intersect($lookup, $value));
        },
        // TODO: methods from this in the entity class!
        // TODO: use corresponding traits, eg EntityChangedTrait;
      ],
      // TODO: schema properties for config entities.
      'entity_keys' => [
        'label' => 'Entity keys',
        'computed' => TRUE,
        'default' => function($component_data) {
          $value = [
            'id' => $component_data['entity_type_id'] . '_id',
            // TOD: further keys.
          ];
          return $value;
        },
      ],
      'entity_interface_name' => [
        'label' => 'Interface',
        'computed' => TRUE,
        'default' => function($component_data) {
          return $component_data['entity_class_name'] . 'Interface';
        },
      ],
    ];

    // Put the parent definitions after ours.
    $data_definition += parent::componentDataDefinition();

    // Override some parent definitions to provide computed defaults.
    $data_definition['relative_class_name']['default'] = function ($component_data) {
      return [
        'Entity',
        $component_data['entity_class_name'],
      ];
    };
    $data_definition['docblock_first_line']['default'] = function ($component_data) {
      return "Provides the {$component_data['entity_type_label']} entity.";
    };

    return $data_definition;
  }

  /**
   * Provides options for the interface_parents property's _long_options.
   *
   * @var array
   *   An array whose keys are fully-qualified interace names and whose values
   *   are descriptions.
   */
  abstract protected static function interfaceParents();

  /**
   * Provides the basic interface that the entity type interface must inherit.
   *
   * @var string
   *   The fully-qualified interface name.
   */
  abstract protected function interfaceBasicParent();

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    //dump($this->component_data);

    $components["entity_type_{$this->component_data['entity_type_id']}_interface"] = [
      'component_type' => 'PHPInterfaceFile',
      'relative_class_name' => [
        'Entity',
        $this->component_data['entity_interface_name'],
      ],
      'docblock_first_line' => "Interface for {$this->component_data['entity_type_label']} entities.",
      'parent_interface_names' => array_merge([
          $this->interfaceBasicParent(),
        ],
        $this->component_data['interface_parents']
      ),
    ];

    return $components;
  }

  // helper. code from https://www.drupal.org/node/66183
  public static function insert(&$array, $key, $insert_array, $before = FALSE) {
    $done = FALSE;
    foreach ($array as $array_key => $array_val) {
      if (!$before) {
        $new_array[$array_key] = $array_val;
      }
      if (!$done && ($array_key == $key)) {
        foreach ($insert_array as $insert_array_key => $insert_array_val) {
          $new_array[$insert_array_key] = $insert_array_val;
        }
        $done = TRUE;
      }
      if ($before) {
        $new_array[$array_key] = $array_val;
      }
    }
    if (!$done) {
      $new_array = array_merge($array, $insert_array);
    }
    // Put the new array in the place of the original.
    $array = $new_array;
  }

}
