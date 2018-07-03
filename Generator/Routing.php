<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for the routing.yml file.
 *
 * Note this only is requested for Drupal 8.
 *
 * @see RouterItem
 */
class Routing extends YMLFile {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $definition = parent::componentDataDefinition();

    $definition['filename']['default'] = "%module.routing.yml";

    return $definition;
  }

}
