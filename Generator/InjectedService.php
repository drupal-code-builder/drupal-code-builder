<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Exception\InvalidInputException;
use CaseConverter\StringAssembler;

/**
 * Generator for a service injection into a class.
 */
class InjectedService extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $data_definition['service_id'] = [
      'label' => 'Service name',
      'required' => TRUE,
      'processing' => function($value, &$component_data, $property_name, &$property_info) {
        // Validate the service name.
        $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
        $services_data = $task_handler_report_services->listServiceData();

        if (!isset($services_data[$value])) {
          throw new InvalidInputException("Service {$value} not found.");
        }

        // Build up the service info.
        $service_info = [];
        $service_info['id'] = $service_id = $value;

        // Copy these explicitly for maintainability and readability.
        $service_info['description']  = $services_data[$service_id]['description'];
        $service_info['interface']    = $services_data[$service_id]['interface'];

        // Derive further information.
        $service_id_pieces = preg_split('@[_.]@', $value);
        $service_info['variable_name'] = (new StringAssembler($service_id_pieces))->snake();
        $service_info['property_name'] = (new StringAssembler($service_id_pieces))->camel();

        // If the service has no interface, typehint on the class.
        $service_info['typehint'] = $service_info['interface'] ?? $service_info['class'];

        // Set the service info.
        // Bit of a cheat, as undeclared data property!
        $component_data['service_info'] = $service_info;
      }
    ];

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $service_info = $this->component_data['service_info'];

    return [
      'service' => [
        'role' => 'service',
        'content' => $service_info,
      ],
      'container_extraction' => [
        'role' => 'container_extraction',
        'content' => "\$container->get('{$service_info['id']}'),",
      ],
      'constructor_param' => [
        'role' => 'constructor_param',
        'content' => [
          'name'        => $service_info['variable_name'],
          'typehint'    => $service_info['typehint'],
          'description' => $service_info['description'] . '.',
        ],
      ],
    ];
  }

}
