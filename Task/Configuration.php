<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\Task\Generate\ComponentClassHandler;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Data\DataItem;

/**
 * Task handler for working with configuration.
 *
 * Configuration consists of options for code generation that users will
 * typically want to apply to all code generation on a project. UIs should allow
 * for a way to store these persistently and pass them to
 * Generate::generateComponent() each time.
 */
class Configuration extends Base {

  protected $sanity_level = 'none';

  /**
   * Constructor.
   *
   * @param $environment
   *  The current environment handler.
   */
  function __construct(EnvironmentInterface $environment, ComponentClassHandler $component_class_handler) {
    $this->environment = $environment;
    $this->componentClassHandler = $component_class_handler;
  }

  /**
   * Returns the data object for the component type's configuration.
   *
   * UIs should use this to present the options to the user.
   *
   * @param string $component_type
   *   The component type, e.g. 'module'.
   */
  public function getConfigurationData(string $component_type): DataItem {
    $class = $this->componentClassHandler->getGeneratorClass($component_type);

    $data = DrupalCodeBuilderDataItemFactory::createFromCallback("{$class}::configurationDefinition");

    return $data;
  }

}
