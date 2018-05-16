<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Represents a single property in a .info file.
 */
class InfoProperty extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'property_name' => [
        'label' => 'The name of the property',
      ],
      // Note that array values are not supported.
      'property_value' =>  [
        'label' => 'The value of the property',
      ],
    ];
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
    return '%nearest_root:info';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
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
