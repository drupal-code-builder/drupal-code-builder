<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Collect8.
 */

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\Task\Collect\HooksCollector;
use DrupalCodeBuilder\Task\Collect\PluginTypesCollector;
use DrupalCodeBuilder\Task\Collect\ServicesCollector;
use DrupalCodeBuilder\Task\Collect\ServiceTagTypesCollector;
use DrupalCodeBuilder\Task\Collect\FieldTypesCollector;
use DrupalCodeBuilder\Task\Collect\DataTypesCollector;
use DrupalCodeBuilder\Task\Collect\AdminRoutesCollector;


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
    'AdminRoutesCollector',
  ];

  /**
   * Constructor.
   */
  function __construct(
    EnvironmentInterface $environment,
    HooksCollector $hooks_collector,
    PluginTypesCollector $plugin_types_collector,
    ServicesCollector $services_collector,
    ServiceTagTypesCollector $service_tag_type_collector,
    FieldTypesCollector $field_types_collector,
    DataTypesCollector $data_types_collector,
    AdminRoutesCollector $admin_routes_collector
  ) {
    $this->environment = $environment;

    $this->collectors = [
      'Collect\HooksCollector' => $hooks_collector,
      'Collect\PluginTypesCollector' => $plugin_types_collector,
      'Collect\ServicesCollector' => $services_collector,
      'Collect\ServiceTagTypesCollector' => $service_tag_type_collector,
      'Collect\FieldTypesCollector' => $field_types_collector,
      'Collect\DataTypesCollector' => $data_types_collector,
      'Collect\AdminRoutesCollector' => $admin_routes_collector,
    ];
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
      $qualified_class = '\DrupalCodeBuilder\Task\Collect\\' . $class;

      switch ($class) {
        case 'HooksCollector':
          $qualified_class .= $this->environment->getCoreMajorVersion();
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
