<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator class for controllers.
 */
class Controller extends PHPClassFileWithInjection {

  /**
   * {@inheritdoc}
   */
  protected const CLASS_DI_INTERFACE = '\Drupal\Core\DependencyInjection\ContainerInjectionInterface';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('use_static_factory_method')
      ->setLiteralDefault(TRUE);
  }

}
