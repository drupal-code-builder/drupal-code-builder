<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator class for controllers.
 */
class Controller extends PHPClassFileWithInjection {

  protected $hasStaticFactoryMethod = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    parent::collectSectionBlocks();

    $this->collectSectionBlocksForDependencyInjection();
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
