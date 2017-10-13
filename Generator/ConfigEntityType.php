<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\AnnotationTrait;

/**
 * Generator for a config entity type.
 */
class ConfigEntityType extends EntityTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

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
  protected function class_doc_block() {
    dump($this->component_data);
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

    $docblock_lines = array_merge($docblock_lines, $this->renderAnnnotation($annotation));

    return $this->docBlock($docblock_lines);
  }


}
