<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Module7.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Drupal 7 version of component.
 */
class Module7 extends Module {

  /**
   * {@inheritdoc}
   */
  protected function componentDataDefinition() {
    $component_data_definition = parent::componentDataDefinition();

    unset($component_data_definition['plugins']);
    unset($component_data_definition['services']);

    return $component_data_definition;
  }

}
