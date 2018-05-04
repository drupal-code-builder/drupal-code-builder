<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for hook implementations for Drupal 6.
 */
class HookImplementation6 extends HookImplementation {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $properties = parent::componentDataDefinition();

    $properties['doxygen_first']['default'] = function($component_data) {
      return "Implementation of {$component_data['hook_name']}().";
    };

    return $properties;
  }

}
