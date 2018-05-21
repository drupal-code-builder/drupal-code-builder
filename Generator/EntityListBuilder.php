<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * Generator for entity list builder handler classes.
 *
 * TODO:
 * - show the type for content entities with a bundle
 * - show the author for content entities with the interface
 * - consider using FormElement or a new related class to make the render
 *   elements.
 */
class EntityListBuilder extends EntityHandler {

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition() + [
      // One of 'config' or 'content'.
      'entity_type_group' => [
        'internal' => TRUE,
      ],
    ];

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $entity_type_is_config = ($this->component_data['entity_type_group'] === 'config');

    $columns = [];

    // The base class for list builders is woefully inadequate, so we need to
    // provide some properties to show in the table.
    $build_header_body = [];
    $build_header_body[] = '£header = [];';

    if ($entity_type_is_config) {
      $build_header_body[] = "£header['name'] = £this->t('Name');";
    }
    else {
      $build_header_body[] = "£header['label'] = £this->t('Label');";
    }

    $build_header_body[] = 'return £header + parent::buildHeader();';

    $components['buildHeader'] = [
      'component_type' => 'PHPFunction',
      'containing_component' => '%requester',
      'docblock_inherit' => TRUE,
      'function_name' => 'buildHeader',
      'declaration' => 'public function buildHeader()',
      'body' => $build_header_body,
    ];

    $build_row_body = [];
    $build_row_body[] = '£row = [];';

    if ($entity_type_is_config) {
      $build_row_body[] = "£row['name'] = £entity->label();";
    }
    else {
      // Content entities have a view page, so link to that.
      $build_row_body[] = "£row['label']['data'] = [";
      $build_row_body[] = "  '#type' => 'link',";
      $build_row_body[] = "  '#title' => £entity->label(),";
      $build_row_body[] = "  '#url' => £entity->toUrl(),";
      $build_row_body[] = '];';
    }

    $build_row_body[] = 'return £row + parent::buildRow(£entity);';

    $components['buildRow'] = [
      'component_type' => 'PHPFunction',
      'containing_component' => '%requester',
      'docblock_inherit' => TRUE,
      'function_name' => 'buildRow',
      'declaration' => 'public function buildRow(\Drupal\Core\Entity\EntityInterface $entity)',
      'body' => $build_row_body,
    ];

    return $components;
  }

}
