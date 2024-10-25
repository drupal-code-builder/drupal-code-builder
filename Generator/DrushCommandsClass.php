<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for a class holding Drus commands.
 */
class DrushCommandsClass extends PHPClassFileWithInjection {

  /**
   * {@inheritdoc}
   */
  protected string $containerInterface = '\\Psr\\Container\\ContainerInterface';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('use_static_factory_method')
      ->setLiteralDefault(TRUE);
  }

}
