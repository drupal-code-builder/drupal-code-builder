<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

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
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->getProperty('filename')->setLiteralDefault("%module.routing.yml");

    return $definition;
  }

}
