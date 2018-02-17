<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\AnnotationTrait;
use CaseConverter\CaseString;

/**
 * Generator for a config entity type.
 */
class ConfigEntityType extends EntityTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $config_schema_property = [
      'entity_properties' => [
        'label' => 'Entity properties',
        'description' => "The config properties that are stored for each entity of this type",
        'format' => 'compound',
        'properties' => [
          'name' => [
            'label' => 'Property name',
            'required' => TRUE,
          ],
          'label' => [
            'label' => 'Property label',
            'default' => function($component_data) {
              $entity_type_id = $component_data['name'];
              return CaseString::snake($entity_type_id)->title();
            },
            'process_default' => TRUE,
          ],
          'type' => [
            'label' => 'Data type',
            'required' => TRUE,
            'options' => 'ReportDataTypes:listDataTypesOptions',
          ],
        ],
      ],
    ];
    self::insert($data_definition, 'interface_parents', $config_schema_property);

    $data_definition['parent_class_name']['default'] = '\Drupal\Core\Config\Entity\ConfigEntityBase';

    $bundle_of_entity_properties = [
      'bundle_of_entity' => [
        'label' => 'Bundle of entity',
        'internal' => TRUE,
      ],
    ];
    self::insert($data_definition, 'entity_class_name', $bundle_of_entity_properties);

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected static function interfaceParents() {
    return [
      'Drupal\Core\Entity\EntityWithPluginCollectionInterface' => 'EntityWithPluginCollectionInterface',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function interfaceBasicParent() {
    return '\Drupal\Core\Config\Entity\ConfigEntityInterface';
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    $entity_config_key = $this->component_data['entity_type_id'];

    $schema_properties_yml = [];
    foreach ($this->component_data['entity_properties'] as $schema_item) {
      $schema_properties_yml[$schema_item['name']] = [
        'type' => $schema_item['type'],
        'label' => $schema_item['label'],
      ];
    }

    $components["config/schema/%module.schema.yml"] = [
      'component_type' => 'ConfigSchema',
      'yaml_data' => [
        "%module.{$entity_config_key}"=> [
          'type' => 'config_entity',
          'label' => $this->component_data['entity_type_label'],
          'mapping' => $schema_properties_yml,
        ],
      ],
    ];

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    // Set up properties.
    foreach ($this->component_data['entity_properties'] as $schema_item) {
      // Just take the label as the description.
      // TODO: add a description property?
      $description = $schema_item['label'];
      if (substr($description, 0, 1) != '.') {
        $description .= '.';
      }

      $this->properties[] = $this->createPropertyBlock(
        $schema_item['name'],
        $schema_item['type'], // TODO: config schema type not the same as PHP type!!!!!
        [
          'docblock_first_line' => $schema_item['label'] . '.',
        ]
        // TODO: default value?
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function class_doc_block() {
    //dump($this->component_data);
    $docblock_lines = [];
    $docblock_lines[] = $this->component_data['docblock_first_line'];
    $docblock_lines[] = '';

    $annotation = [
      '#class' => 'ConfigEntityType',
      '#data' => [
        'id' => $this->component_data['entity_type_id'],
        'label' => [
          '#class' => 'Translation',
          '#data' => $this->component_data['entity_type_label'],
        ],
      ],
    ];

    if (isset($this->component_data['bundle_of_entity'])) {
      $annotation['#data']['bundle_of'] = $this->component_data['bundle_of_entity'];
    }

    $annotation['#data'] += [
      'entity_keys' => $this->component_data['entity_keys'],
    ];

    $config_export_values = [];
    foreach ($this->component_data['entity_properties'] as $schema_item) {
      $config_export_values[] = $schema_item['name'];
    }
    if ($config_export_values) {
      $annotation['#data'] += [
        'config_export' => $config_export_values,
      ];
    }

    $docblock_lines = array_merge($docblock_lines, $this->renderAnnnotation($annotation));

    return $this->docBlock($docblock_lines);
  }


}
