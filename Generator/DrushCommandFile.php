<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * Generator for the class holding Drush command methods.
 */
class DrushCommandFile extends PHPClassFileWithInjection {

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $data_definition += [
      'injected_services' => [
        'acquired' => TRUE,
        'format' => 'array',
      ],
    ];

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return 'drush-commands';
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    // dump($this->component_data);
    $components = [];

    return $components;
  }

}
