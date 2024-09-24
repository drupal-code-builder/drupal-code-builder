<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;

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
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('filename')->setLiteralDefault("%module.routing.yml");
  }

}
