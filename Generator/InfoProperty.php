<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Represents a single property in an extension's info.
 */
class InfoProperty extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      // An array of requested hooks, as long hook names.
      'property_name' => PropertyDefinition::create('string')
        ->setLabel('The name of the property')
        ->setInternal(TRUE),
      // Note that array values are not supported.
      'property_value' => PropertyDefinition::create('string')
        ->setLabel('The value of the property')
        ->setInternal(TRUE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    // We don't need to request the info component, as that's always required.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%nearest_root:info';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    return [
      $this->component_data['property_name'] => $this->component_data['property_value'],
    ];
  }

}
