<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for a class holding Drush commands.
 */
class DrushCommandsClass extends PHPClassFileWithInjection {

  /**
   * {@inheritdoc}
   */
  protected const CONTAINER_INTERFACE = '\\Psr\\Container\\ContainerInterface';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('use_static_factory_method')
      ->setLiteralDefault(TRUE);
  }

}
