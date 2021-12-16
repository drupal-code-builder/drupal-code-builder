<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\Task\Collect\ElementTypesCollector;
use DrupalCodeBuilder\Task\Collect\EntityTypesCollector;
use DrupalCodeBuilder\Task\Collect\HooksCollector;
use DrupalCodeBuilder\Task\Collect\PluginTypesCollector;
use DrupalCodeBuilder\Task\Collect\ServicesCollector;
use DrupalCodeBuilder\Task\Collect\ServiceTagTypesCollector;
use DrupalCodeBuilder\Task\Collect\FieldTypesCollector;
use DrupalCodeBuilder\Task\Collect\DataTypesCollector;
use DrupalCodeBuilder\Task\Collect\AdminRoutesCollector;

/**
 * Task handler for collecting and processing component definitions.
 */
class Collect9 extends Collect {

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
    EntityTypesCollector $entity_types_collector,
    ElementTypesCollector $element_types_collector,
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
      'Collect\EntityTypesCollector' => $entity_types_collector,
      'Collect\ElementTypesCollector' => $element_types_collector,
      'Collect\AdminRoutesCollector' => $admin_routes_collector,
    ];
  }

}
