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
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('use_static_factory_method')
      ->setLiteralDefault(TRUE);
  }

  /**
   * Produces the class declaration.
   */
  function classDeclaration() {
    if (isset($this->containedComponents['injected_service'])) {
      // Numeric key will clobber, so make something up!
      // TODO: fix!
      $this->component_data->interfaces->add(['ContainerInjectionInterface' => '\Drupal\Core\DependencyInjection\ContainerInjectionInterface']);
    }

    return parent::classDeclaration();
  }

}
