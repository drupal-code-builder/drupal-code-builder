<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\AnnotationTrait;
use DrupalCodeBuilder\Utility\InsertArray;
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
      'fieldable' => [
        'label' => 'Fieldable',
        'description' => "Whether this entity type allows custom fields.",
        'format' => 'boolean',
        'default' => TRUE,
      ],
      'translatable' => [
        'label' => 'Translatable',
        'description' => "Whether this entity type allows translation.",
        'format' => 'boolean',
        'default' => TRUE,
      ],
      'bundle_entity' => [
        'label' => 'Bundle config entity type',
        'description' => "Creates a config entity type which provides the bundles for this entity type. "
          . "This is analogous to the Node Type entity type providing bundles for the Node entity type.",
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
      'bundle_label' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          if (isset($component_data['bundle_entity'][0]['entity_type_id'])) {
            // TODO: get the actual value of the entity_type_label property from
            // the bundle entity -- but this is proving rather labyrinthine...
            return CaseString::snake($component_data['bundle_entity'][0]['entity_type_id'])->title();
          }
        },
      ],
      'field_ui_base_route' => [
        'label' => 'Field UI base route',
        // TODO: expose to UI in 3.3 when we have dynamic defaults.
        // This will then be dependent on the 'fieldable' property.
        'computed' => TRUE,
        'default' => function($component_data) {
          if (empty($component_data['fieldable'])) {
            return NULL;
          }

          if (isset($component_data['bundle_entity'][0]['entity_type_id'])) {
            return 'entity.' . $component_data['bundle_entity'][0]['entity_type_id'] . '.edit_form';
          }
          else {
            return 'entity.' . $component_data['entity_type_id'] . '.admin_form';
          }
        },
      ],
    ];
    InsertArray::insertAfter($data_definition, 'entity_class_name', $bundle_entity_properties);

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
    InsertArray::insertAfter($data_definition, 'interface_parents', $base_fields_property);

    $data_definition['bundle_entity_type'] = [
      'label' => 'Bundle entity type',
      'format' => 'string',
      'internal' => TRUE,
      'default' => function($component_data) {
        return $component_data['bundle_entity'][0]['entity_type_id'] ?? NULL;
      },
    ];

    $data_definition['parent_class_name']['default'] = '\Drupal\Core\Entity\ContentEntityBase';

    // Change the computed value for entity keys.
    $data_definition['entity_keys']['default'] = function($component_data) {
      $keys = [
        'id' => $component_data['entity_type_id'] . '_id',
      ];

      if (!empty($component_data['translatable'])) {
        $keys['langcode'] = 'langcode';
      }

      return $keys;
    };

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getHandlerTypes() {
    $handler_types = parent::getHandlerTypes() + [
      'view_builder' => [
        'label' => 'view builder',
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\EntityViewBuilder',
      ],
      "views_data" => [
        'label' => 'Views data',
        // Core leaves empty if not specified.
        'mode' => 'core_none',
        'base_class' => '\Drupal\views\EntityViewsData',
      ],
      'translation' => [
        'label' => 'translation',
        // Technically, the content_translation module does this rather than the
        // entity system.
        'mode' => 'core_default',
        'base_class' => '\Drupal\content_translation\ContentTranslationHandler',
      ],
      // routing: several options...
    ];

    $handler_types['storage']['base_class'] = '\Drupal\Core\Entity\Sql\SqlContentEntityStorage';

    $storage_schema_type = [
      'storage_schema' => [
        'label' => 'storage schema',
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema',
      ],
    ];
    InsertArray::insertAfter($handler_types, 'storage', $storage_schema_type);

    return $handler_types;
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

      $fluent_calls = [];
      $fluent_calls[] = "  ->setLabel(t('{$base_field_data['label']}'))";
      $fluent_calls[] = "  ->setDescription(t('TODO: description of field.'))";
      if (!empty($this->component_data['translatable'])) {
        $fluent_calls[] = "  ->setTranslatable(TRUE)";
      }
      // Add a terminal ';' to the last of the fluent method calls.
      $fluent_calls[count($fluent_calls) - 1] .= ';';

      $method_body = array_merge($method_body, $fluent_calls);

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
      'data_table',
      'translatable',
      'handlers',
      'admin_permission',
      'entity_keys',
      'bundle_entity_type',
      'field_ui_base_route',
    ];
    $annotation_data = array_fill_keys($annotation_keys, NULL);

    // Re-create the annotation #data array, with the properties in our set
    // order.
    foreach ($annotation['#data'] as $key => $data) {
      $annotation_data[$key] = $data;
    }

    // Add further annotation properties.
    // Use the entity type ID as the base table.
    $annotation_data['base_table'] = $this->component_data['entity_type_id'];

    if (isset($this->component_data['bundle_entity_type'])) {
      $annotation_data['bundle_entity_type'] = $this->component_data['bundle_entity_type'];
      $annotation_data['bundle_label'] = [
        '#class' => 'Translation',
        '#data' => $this->component_data['bundle_label'],
      ];
    }

    if (!empty($this->component_data['field_ui_base_route'])) {
      $annotation_data['field_ui_base_route'] = $this->component_data['field_ui_base_route'];
    }

    if (!empty($this->component_data['translatable'])) {
      $annotation_data['translatable'] = 'TRUE';
      $annotation_data['data_table'] = "{$annotation_data['base_table']}_field_data";
    }

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
