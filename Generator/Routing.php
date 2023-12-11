<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for the routing.yml file.
 *
 * Note this only note requested for Drupal 7 and older.
 *
 * @see RouterItem
 */
class Routing extends YMLFile {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
    $definition = parent::getPropertyDefinition();

    $definition->getProperty('filename')->setLiteralDefault("%module.routing.yml");

    return $definition;
  }

}
