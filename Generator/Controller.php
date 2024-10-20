<?php

namespace DrupalCodeBuilder\Generator;


/**
 * Generator class for controllers.
 */
class Controller extends PHPClassFileWithInjection {

  protected $hasStaticFactoryMethod = TRUE;

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
