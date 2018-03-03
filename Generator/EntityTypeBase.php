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
          return CaseString::snake($entity_type_id)->title();
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

    // Default property values for a handler that core fills in if not
    // specified, e.g. the access handler.
    $handler_property_defaults_core_default = [
      'format' => 'boolean',
    ];

    // Default property values for a handler that core leaves empty if not
    // specified, e.g. the list builder handler.
    $handler_property_defaults_core_empty = [
      'format' => 'string',
      'options' => [
        'none' => 'Do not use a handler',
        'core' => 'Use the core handler class',
        'custom' => 'Provide a custom handler class',
      ],
    ];

    foreach (static::getHandlerTypes() as $key => $handler_type_info) {
      if ($handler_type_info['mode'] == 'core_default') {
        $handler_property = $handler_property_defaults_core_default;

        $handler_property['label'] = "Custom {$handler_type_info['label']} handler";
      }
      else {
        $handler_property = $handler_property_defaults_core_empty;

        $handler_property['label'] = "{$handler_type_info['label']} handler";
      }

      $handler_property['handler_label'] = $handler_type_info['label'];
      $handler_property['parent_class_name'] = $handler_type_info['base_class'];

      $data_definition["handler_{$key}"] = $handler_property;
    }

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
   * Lists the available handler types.
   *
   * @return array
   *   An array whose keys are the handler type, e.g. 'access', and whose values
   *   are arrays containing:
   *   - 'label': The label of the handler type, in lowercase.
   *   - 'base_class': The base class for handlers of this type.
   *   - 'mode': Defines how the core entity system handles an entity not
   *     defining a handler of this type. One of:
   *      - 'core_default': A default handler is provided.
   *      - 'core_none': No handler is provided.
   */
  protected static function getHandlerTypes() {
    return [
      'access' => [
        'label' => 'access',
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\EntityAccessControlHandler',
      ],
      'storage' => [
        'label' => 'storage',
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\EntityStorageBase',
      ],
      'list_builder' => [
        'label' => 'list builder',
        'mode' => 'core_none',
        'base_class' => '\Drupal\Core\Entity\EntityListBuilder',
      ],
    ];
  }

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

    // Handlers.
    foreach (static::getHandlerTypes() as $key => $handler_type_info) {
      $data_key = "handler_{$key}";

      // Skip if nothing in the data.
      if (empty($this->component_data[$data_key])) {
        continue;
      }

      // For handlers that core doesn't fill in, only provide a class if
      // for the 'custom' option.
      if ($handler_type_info['mode'] == 'core_none') {
        if ($this->component_data[$data_key] != 'custom') {
          continue;
        }
      }

      $components[$data_key] = [
        'component_type' => 'EntityHandler',
        'entity_class_name' => $this->component_data['entity_class_name'],
        'entity_type_label' => $this->component_data['entity_type_label'],
        'handler_type' => $key,
        'handler_label' => $handler_type_info['label'],
        'parent_class_name' => $handler_type_info['base_class'],
      ];
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function getClassDocBlockLines() {
    //dump($this->component_data);
    $docblock_lines = parent::getClassDocBlockLines();
    $docblock_lines[] = '';

    $annotation = $this->getAnnotationData();

    $docblock_lines = array_merge($docblock_lines, $this->renderAnnnotation($annotation));

    return $docblock_lines;
  }

  /**
   * Gets the data for the annotation.
   *
   * @return array
   *   A data array suitable for renderAnnnotation().
   */
  protected function getAnnotationData() {
    $annotation = [
      '#class' => 'CHILD CLASS SETS THIS',
      '#data' => [
        'id' => $this->component_data['entity_type_id'],
        'label' => [
          '#class' => 'Translation',
          '#data' => $this->component_data['entity_type_label'],
        ],
      ],
    ];

    $annotation['#data'] += [
      'entity_keys' => $this->component_data['entity_keys'],
    ];

    // Handlers.
    $handler_data = [];
    foreach (static::getHandlerTypes() as $key => $handler_type_info) {
      $data_key = "handler_{$key}";

      // Skip if nothing in the data.
      if (empty($this->component_data[$data_key])) {
        continue;
      }

      if ($handler_type_info['mode'] == 'core_none' && $this->component_data[$data_key] == 'core') {
        $handler_class = substr($handler_type_info['base_class'], 1);
      }
      else {
        $handler_class = static::makeQualifiedClassName([
          // TODO: DRY, with EntityHandler class.
          'Drupal',
          '%module',
          'Entity',
          'Handler',
          $this->component_data['entity_class_name'] .  CaseString::snake($key)->pascal(),
        ]);
      }

      $handler_data[$key] = $handler_class;
    }
    if ($handler_data) {
      $annotation['#data']['handlers'] = $handler_data;
    }

    return $annotation;
  }

}
