<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Collect8.
 */

namespace DrupalCodeBuilder\Task;

/**
 * Task handler for collecting and processing component definitions.
 *
 * This collects data on hooks and plugin types.
 */
class Collect8 extends Collect {

  /**
   * The short names of classes in this namespace that are collectors.
   *
   * @var string[]
   */
  protected $collectorClassNames = [
    'HooksCollector',
    'PluginTypesCollector',
    'ServicesCollector',
    'ServiceTagTypesCollector',
    'FieldTypesCollector',
    'DataTypesCollector',
  ];

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
      $qualified_class = '\DrupalCodeBuilder\Task\Collect\\' . $class;

      switch ($class) {
        case 'HooksCollector':
          $qualified_class .= '8';
          $helper = new $qualified_class($this->environment);
          break;
        case 'PluginTypesCollector':
          $helper = new $qualified_class(
            $this->environment,
            $this->getHelper('ContainerBuilderGetter'),
            $this->getHelper('MethodCollector'),
            $this->getHelper('CodeAnalyser')
          );
          break;
        case 'ServiceTagTypesCollector':
          $helper = new $qualified_class(
            $this->environment,
            $this->getHelper('ContainerBuilderGetter'),
            $this->getHelper('MethodCollector')
          );
          break;
        case 'ServicesCollector':
          $helper = new $qualified_class(
            $this->environment,
            $this->getHelper('ContainerBuilderGetter'),
            $this->getHelper('CodeAnalyser')
          );
          break;
        default:
          $helper = new $qualified_class($this->environment);
          break;
      }

      $this->helpers[$class] = $helper;
    }

    return $this->helpers[$class];
  }

}
