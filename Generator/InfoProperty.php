<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\InfoProperty.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Represents a single property in a .info file.
 */
class InfoProperty extends BaseGenerator {

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   An array of data for the component.
   *   Valid properties are:
   *      - 'property_name': The name of the property.
   *      - 'property_value': The value of the property. Note that arrays are
   *        not supported.
   */
  function __construct($component_name, $component_data, $root_generator) {
    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    // We don't need to request the .info file, as that's always required.
    return array();
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return 'Info:info';
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    $data = [
      'property' => [
        'role' => 'infoline',
        'content' => [
          $this->component_data['property_name'] => $this->component_data['property_value']
        ],
      ]
    ];
    return $data;
  }

}
