<?php

namespace DrupalCodeBuilder\Task;

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
   * Returns the data object for the component type's configuration.
   *
   * UIs should use this to present the options to the user.
   *
   * @param string $component_type
   *   The component type, e.g. 'module'.
   */
  public function getConfigurationData(string $component_type): DataItem {
    $class = $this->getHelper('ComponentClassHandler')->getGeneratorClass($component_type);

    $data = DrupalCodeBuilderDataItemFactory::createFromCallback("{$class}::configurationDefinition");

    return $data;
  }

  /**
   * Returns the helper for the given short class name.
   *
   * @param $class
   *   The short class name.
   *
   * @return
   *   The helper object.
   */
  protected function getHelper($class) {
    if (!isset($this->helpers[$class])) {
      $qualified_class = '\DrupalCodeBuilder\Task\Generate\\' . $class;

      switch ($class) {
        case 'ComponentDataInfoGatherer':
          $helper = new $qualified_class($this->getHelper('ComponentClassHandler'));
          break;
        case 'ComponentCollector':
          $helper = new $qualified_class(
            $this->environment,
            $this->getHelper('ComponentClassHandler'),
            $this->getHelper('ComponentDataInfoGatherer')
          );
          break;
        default:
          $helper = new $qualified_class();
          break;
      }

      $this->helpers[$class] = $helper;
    }

    return $this->helpers[$class];
  }

}
