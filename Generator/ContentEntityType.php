<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\AnnotationTrait;
use CaseConverter\CaseString;

/**
 * Generator for a content entity type.
 */
class ContentEntityType extends EntityTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $bundle_entity_properties = [
      'bundle_entity' => [
        'label' => 'Bundle config entity',
        'format' => 'compound',
        'cardinality' => 1,
        'component' => 'ConfigEntityType',
        'default' => function($component_data) {
          return [
            0 => [
              // The bundle entity type ID defaults to CONTENT_TYPE_type.
              'entity_type_id' => $component_data['entity_type_id'] . '_type',
              'bundle_of_entity' => $component_data['entity_type_id'],
            ],
          ];
        },
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          // Fill in defaults if an item is requested.
          // Bit faffy, but needed for non-progressive UIs.
          if (isset($component_data['bundle_entity'][0]['entity_type_id'])) {
            $component_data['bundle_entity'][0]['bundle_of_entity'] = $component_data['entity_type_id'];
          }
        },
      ],
    ];
    self::insert($data_definition, 'entity_class_name', $bundle_entity_properties);

    $base_fields_property = [
      'base_fields' => [
        'label' => 'Base fields',
        'description' => "The base fields for this content entity.",
        'format' => 'compound',
        // TODO: default, populated by things such as interface choice!
        'properties' => [
          'name' => [
            'label' => 'Field name',
            'required' => TRUE,
          ],
          'label' => [
            'label' => 'Field label',
            'default' => function($component_data) {
              $entity_type_id = $component_data['name'];
              return CaseString::snake($entity_type_id)->title();
            },
            'process_default' => TRUE,
          ],
          'type' => [
            'label' => 'Field type',
            'required' => TRUE,
            'options' => 'ReportFieldTypes:listFieldTypesOptions',
          ],
        ],
      ],
    ];
    self::insert($data_definition, 'interface_parents', $base_fields_property);

    $data_definition['bundle_entity_type'] = [
      'label' => 'Bundle entity type',
      'format' => 'string',
      'internal' => TRUE,
      'default' => function($component_data) {
        return $component_data['bundle_entity'][0]['entity_type_id'] ?? NULL;
      },
    ];

    $data_definition['parent_class_name']['default'] = '\Drupal\Core\Entity\ContentEntityBase';

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

    return $data_definition;
  }

  protected static function getHandlerTypes() {
    return [
      'storage' => [
        'label' => 'storage',
        // Core fills this in if entity type doesn't specify.
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\Sql\SqlContentEntityStorage',
      ],
      'view_builder' => [
        'label' => 'view builder',
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\EntityViewBuilder',
      ],
      'list_builder' => [
        'label' => 'list builder',
        'mode' => 'core_none',
        'base_class' => '\Drupal\Core\Entity\EntityListBuilder',
      ],
      "views_data" => [
        'label' => 'Views data',
        // Core leaves empty if not specified.
        'mode' => 'core_none',
        'base_class' => '\Drupal\views\EntityViewsData',
      ],
      // TODO: this belongs in the base entity type; config have them too.
      'access' => [
        'label' => 'access',
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\EntityAccessControlHandler',
      ],
      // routing: several options...
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static function interfaceParents() {
    return [
      '\Drupal\Core\Entity\EntityChangedInterface' => 'EntityChangedInterface, for entities that store a timestamp for their last change',
      '\Drupal\user\EntityOwnerInterface' => 'EntityOwnerInterface, for entities that have an owner',
      // EntityPublishedInterface? but this has its own base class.
      // EntityDescriptionInterface? but only used in core for config.
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function interfaceBasicParent() {
    return '\Drupal\Core\Entity\ContentEntityInterface';
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    //dump($this->component_data);

    $method_body = [];
    // Calling the parent defines fields for entity keys.
    $method_body[] = '$fields = parent::baseFieldDefinitions($entity_type);';
    $method_body[] = '';
    foreach ($this->component_data['base_fields'] as $base_field_data) {
      $method_body[] = "£fields['{$base_field_data['name']}'] = \Drupal\Core\Field\BaseFieldDefinition::create('{$base_field_data['type']}')";
      $method_body[] = "  ->setLabel(t('{$base_field_data['label']}'))";
      $method_body[] = "  ->setDescription(t('TODO: description of field.'));";

      $method_body[] = '';
    }

    $method_body[] = 'return $fields;';

    $components["baseFieldDefinitions"] = [
      'component_type' => 'PHPMethod',
      'containing_component' => '%requester',
      'declaration' => 'public static function baseFieldDefinitions(\Drupal\Core\Entity\EntityTypeInterface $entity_type)',
      'doxygen_first' => '{@inheritdoc}',
      'body' => $method_body,
    ];

    // TODO: other methods!

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
  protected function getAnnotationData() {
    $annotation = parent::getAnnotationData();

    $annotation['#class'] = 'ContentEntityType';

    // Standard ordering for our annotation keys.
    $annotation_keys = [
      'id',
      'label',
      'label_collection',
      'label_singular',
      'label_plural',
      'label_count',
      'bundle_label',
      'base_table',
      'handlers',
      'fieldable',
      'entity_keys',
      'bundle_entity_type',
    ];
    $annotation_data = array_fill_keys($annotation_keys, NULL);

    // Re-create the annotation #data array, with the properties in our set
    // order.
    foreach ($annotation['#data'] as $key => $data) {
      $annotation_data[$key] = $data;
    }

    // Add further annotation properties.
    $annotation_data['label_collection'] = [
      '#class' => 'Translation',
      '#data' => $this->component_data['entity_type_label'] . 's',
    ];
    $annotation_data['label_singular'] = [
      '#class' => 'Translation',
      '#data' => strtolower($this->component_data['entity_type_label']),
    ];
    $annotation_data['label_plural'] = [
      '#class' => 'Translation',
      '#data' => strtolower($this->component_data['entity_type_label']) . 's',
    ];
    $annotation_data['label_count'] = [
      '#class' => 'PluralTranslation',
      '#data' => [
        'singular' => "@count " . strtolower($this->component_data['entity_type_label']),
        'plural' => "@count " . strtolower($this->component_data['entity_type_label']) . 's',
      ],
    ];

    // Use the entity type ID as the base table.
    $annotation_data['base_table'] = $this->component_data['entity_type_id'];

    if (isset($this->component_data['bundle_entity_type'])) {
      $annotation_data['bundle_entity_type'] = $this->component_data['bundle_entity_type'];
      // This gets set into the child component data when the compound property
      // gets prepared and defaults set.
      $annotation_data['bundle_label'] = $this->component_data['entity_type_label'];
    }

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
      $annotation_data['handlers'] = $handler_data;
    }

    $annotation_data['fieldable'] = TRUE;

    // Filter the annotation data to remove any keys which are NULL; that is,
    // which are still in the state that the array fill put them in and that
    // have not had any actual data in. AFAIK annotation values are never
    // actually NULL, so this is ok.
    $annotation_data = array_filter($annotation_data, function($item) {
      return !is_null($item);
    });

    // Put our data into the annotation array.
    $annotation['#data'] = $annotation_data;

    return $annotation;
  }

}
