<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\AnnotationTrait;

/**
 * Generator for a content entity type.
 */
class ContentEntityType extends PHPClassFile {

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
          return self::snakeToCamel($entity_type_id);
        },
      ],
      'interface_parents' => [
        'label' => 'Interface parents',
        'description' => "The interfaces the entity interface inherits from.",
        'format' => 'array',
        // Data for the options and processing callbacks.
        '_long_options' => [
          '\Drupal\Core\Entity\EntityChangedInterface' => 'EntityChangedInterface, for entities that store a timestamp for their last change',
          '\Drupal\user\EntityOwnerInterface' => 'EntityOwnerInterface, for entities that have an owner',
          // EntityPublishedInterface? but this has its own base class.
          // EntityDescriptionInterface? but only used in core for config.
        ],
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
        'processing' => function($value, &$component_data, &$property_info) {
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
      'base_fields' => [
        'label' => 'Base fields',
        'format' => 'compound',
        // TODO: default, populated by things such as interface choice!
        'properties' => [
          'name' => [
            'label' => 'Field name',
          ],
          'label' => [
            'label' => 'Field label',
            'default' => function($component_data) {
              $entity_type_id = $component_data['name'];
              return self::snakeToTitle($entity_type_id);
            },
            // TODO: Doesn't work yet due to bug in ComponentCollector!
            'process_default' => TRUE,
          ],
          'type' => [
            'label' => 'Field type',
            'options' => [
              // TODO: get these from Field/FieldType plugins, via collection,
              // or from the environment on the fly.
              'string' => 'A simple string.',
              'boolean' => 'A boolean stored as an integer.',
              'integer' => 'An integer, with settings for min and max value validation (also provided for decimal and float)',
              'decimal' => 'A decimal with configurable precision and scale.',
              'float' => 'A float number',
              'language' => 'Contains a language code and the language as a computed property',
              'timestamp' => 'A Unix timestamp stored as an integer',
              'created' => 'A timestamp that uses the current time as a default value.',
              'changed' => 'A timestamp that is automatically updated to the current time if the entity is saved.',
              'datetime' => 'A date stored as an ISO 8601 string.',
              'uri' => 'Contains a URI. The link module also provides a link field type that can include a link title and can point to an internal or external URI/route.',
              'uuid' => 'A UUID field that generates a new UUID as the default value.',
              'email' => 'An email, with corresponding validation and widgets and formatters.',
              'entity_reference' => 'An entity reference with a target_id and a computed entity field property. entity_reference.module provides widgets and formatters when enabled.',
              // Broken in core!
              //'map' => 'Can contain any number of arbitrary properties, stored as a serialized string',
            ],
          ],
        ],
      ],
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
          '\Drupal\Core\Entity\ContentEntityInterface',
        ],
        $this->component_data['interface_parents']
      ),
    ];

    $method_body = [];
    // Calling the parent defines fields for entity keys.
    $method_body[] = '$fields = parent::baseFieldDefinitions($entity_type);';
    $method_body[] = '';
    foreach ($this->component_data['base_fields'] as $base_field_data) {
      $method_body[] = "£fields['{$base_field_data['name']}'] = \Drupal\Core\Field\BaseFieldDefinition::create('{$base_field_data['type']}')'";
      $method_body[] = "  ->setLabel(t('{$base_field_data['label']}'))";
      $method_body[] = "  ->setDescription(t('TODO: description of field.'));";

      $method_body[] = '';
    }

    $method_body[] = 'return $fields;';

    $components["baseFieldDefinitions"] = [
      'component_type' => 'PHPMethod',
      'code_file_id' => $this->getUniqueID(),
      'declaration' => 'public static function baseFieldDefinitions(EntityTypeInterface $entity_type)',
      'body' => $method_body,
    ];

    // TODO: other methods!

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function class_doc_block() {
    $docblock_lines = [];
    $docblock_lines[] = $this->component_data['docblock_first_line'];
    $docblock_lines[] = '';

    $annotation = [
      '#class' => 'ContentEntityType',
      '#data' => [
        'id' => $this->component_data['entity_type_id'],
        'label' => [
          '#class' => 'Translation',
          '#data' => $this->component_data['entity_type_label'],
        ],
        'label_collection' => [
          '#class' => 'Translation',
          '#data' => $this->component_data['entity_type_label'] . 's',
        ],
        'label_singular' => [
          '#class' => 'Translation',
          '#data' => strtolower($this->component_data['entity_type_label']),
        ],
        'label_plural' => [
          '#class' => 'Translation',
          '#data' => strtolower($this->component_data['entity_type_label']) . 's',
        ],
        'label_count' => [
          '#class' => 'PluralTranslation',
          '#data' => [
            'singular' => "@count license",
            'plural' => "@count licenses",
          ],
        ],
        // TODO: bundle stuff
        // TODO: handlers
        // Use the entity type ID as the base table.
        'base_table' => $this->component_data['entity_type_id'],
        'label_plural' => 'administer ' . strtolower($this->component_data['entity_type_label']) . 's',
        'fieldable' => TRUE,
        'entity_keys' => $this->component_data['entity_keys'],
      ],
    ];

    $docblock_lines = array_merge($docblock_lines, $this->renderAnnnotation($annotation));

    return $this->docBlock($docblock_lines);
  }

}
