<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Utility\InsertArray;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for a config entity type that is a content entity type's bundle.
 *
 * TODO: move code for bundles that's still in ConfigEntityType to here.
 */
class ConfigBundleEntityType extends ConfigEntityType {

  /**
   * {@inheritdoc}
   */
  protected $annotationTopLevelOrder = [
    'id',
    'label',
    'label_collection',
    'label_singular',
    'label_plural',
    'label_count',
    'handlers',
    'admin_permission',
    'bundle_of',
    'entity_keys',
    'config_export',
    'links',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    // Add this right at the start, before the ID, so the ID default value
    // can depend on it. TODO? order doesn't matter any more?
    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('bundle_entity_type_id')
      ->setAutoAcquiredFromRequester()
    );

    // Allow the entity type ID to be derived from the entity it's a bundle
    // for a content entity type.
    $definition->getProperty('entity_type_id')->setDefault(
      DefaultDefinition::create()
        // TODO: make this work in the form!
        ->setExpression("get('..:..:bundle_entity_type_id')")
    );

    // Bundle entities need to use ConfigEntityBundleBase in order to clear
    // caches and synchronize display entities.
    $definition->getProperty('parent_class_name')->setDefault(
      DefaultDefinition::create()->setLiteral('\Drupal\Core\Config\Entity\ConfigEntityBundleBase')
    );

    return $definition;
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

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // For bundle entity types, the entity ID length is limited.
    foreach ($components as $key => $component) {
      if ($component['component_type'] == 'FormElement' && $component['form_key'] == 'id') {
        $components[$key]['element_array']['maxlength'] =
          '\Drupal\Core\Entity\EntityTypeInterface::BUNDLE_MAX_LENGTH';
      }
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAnnotationData() {
    $annotation_data = parent::getAnnotationData();

    // This is reaching into the parent, which breaks the pattern that a
    // generator should be independent of whatever includes its data definition,
    // but in this case, a bundle entity type is only ever going to be used by a
    // content entity type.
    $annotation_data['bundle_of'] = $this->component_data->getParent()->entity_type_id->value;

    return $annotation_data;
  }

}
