<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Utility\InsertArray;

/**
 * Generator for a config entity type that is a content entity type's bundle.
 *
 * TODO: move code for bundles that's still in ConfigEntityType to here.
 */
class ConfigBundleEntityType extends ConfigEntityType {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $bundle_entity_properties = [
      // Just serves as a stepping-stone to allow this generator's
      // entity_type_id to use it.
      'bundle_entity_type_id' => [
        'internal' => TRUE,
        'acquired' => TRUE,
      ],
      'bundle_of_entity' => [
        'label' => 'Bundle of entity',
        'internal' => TRUE,
        'acquired' => TRUE,
        'acquired_from' => 'entity_type_id',
      ],
    ];
    // Add this right at the start, before the ID, so the ID default value
    // can depend on it.
    InsertArray::insertBefore($data_definition, 'entity_type_id', $bundle_entity_properties);

    // Allow the entity type ID to be derived from the entity it's a bundle
    // for a content entity type.
    $data_definition['entity_type_id']['default'] = function($component_data) {
      // For non-progressive UIs, acquired properties won't be set yet.
      return $component_data['bundle_entity_type_id'] ?? '';
    };
    $data_definition['entity_type_id']['process_default'] = TRUE;

    // Bundle entities need to use ConfigEntityBundleBase in order to clear
    // caches and synchronize display entities.
    $data_definition['parent_class_name']['default'] = '\Drupal\Core\Config\Entity\ConfigEntityBundleBase';

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getHandlerTypes() {
    $handler_types = parent::getHandlerTypes();

    foreach (['form_default', 'form_add', 'form_edit'] as $form_handler_type) {
      // Change the base class of form handlers.
      $handler_types[$form_handler_type]['base_class'] = '\Drupal\Core\Entity\BundleEntityFormBase';
    }

    return $handler_types;
  }

}
