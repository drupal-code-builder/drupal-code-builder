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
   *  Helper objects.
   *
   * @var array
   */
  protected $helpers = [];

  /**
   * {@inheritdoc}
   */
  public function collectComponentData() {
    $result = $this->collectHooks();
    $result += $this->collectPluginTypes();
    $result += $this->collectServices();
    $result += $this->collectServiceTagTypes();
    $result += $this->collectFieldTypes();
    $result += $this->collectDataTypes();

    return $result;
  }

  /**
   * Collect data about plugin types and process it.
   *
   * @return
   *  A summary in the same format as returned by collectComponentData().
   */
  protected function collectPluginTypes() {
    $plugin_type_data = $this->getHelper('PluginTypesCollector')->collect();

    // Save the data.
    $this->environment->getStorage()->store('plugins', $plugin_type_data);

    return ['plugin types' => count($plugin_type_data)];
  }

  /**
   * Collect data about services.
   *
   * @return
   *  A summary in the same format as returned by collectComponentData().
   */
  protected function collectServices() {
    $service_definitions = $this->getHelper('ServicesCollector')->collect();

    // Save the data.
    $this->environment->getStorage()->store('services', $service_definitions);

    return ['services' => count($service_definitions['all'])];
  }

  /**
   * Collect data about tagged service types.
   *
   * @return
   *  A summary in the same format as returned by collectComponentData().
   */
  protected function collectServiceTagTypes() {
    $service_tag_type_definitions = $this->getHelper('ServiceTagTypesCollector')->collectServiceTagTypes();

    // Save the data.
    $this->environment->getStorage()->store('service_tag_types', $service_tag_type_definitions);

    return ['tagged service types' => count($service_tag_type_definitions)];
  }

  /**
   * Collect data about field_type types.
   *
   * @return
   *  A summary in the same format as returned by collectComponentData().
   */
  protected function collectFieldTypes() {
    $field_type_definitions = $this->getHelper('FieldTypesCollector')->collectFieldTypes();

    // Save the data.
    $this->environment->getStorage()->store('field_types', $field_type_definitions);

    return ['field types' => count($field_type_definitions)];
  }

  /**
   * Collect data about config data types.
   *
   * @return
   *  A summary in the same format as returned by collectComponentData().
   */
  protected function collectDataTypes() {
    $data_type_definitions = $this->getHelper('DataTypesCollector')->collect();

    // Save the data.
    $this->environment->getStorage()->store('data_types', $data_type_definitions);

    return ['data types' => count($data_type_definitions)];
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
