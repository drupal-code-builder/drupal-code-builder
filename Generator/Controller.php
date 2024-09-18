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
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    foreach ($this->component_data['injected_services'] as $service_id) {
      $components['service_' . $service_id] = [
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
        'class_has_static_factory' => $this->hasStaticFactoryMethod,
        'class_has_constructor' => TRUE,
        'class_name' => $this->component_data->qualified_class_name->value,
      ];
    }

    return $components;
  }

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
