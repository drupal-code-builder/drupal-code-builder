<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\InjectedService.
 */

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Exception\InvalidInputException;
use CaseConverter\CaseString;

/**
 * Generator for a service injection into a class.
 */
class InjectedService extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    $service_id = $this->component_data['service_id'];
    $id_pieces = preg_split('@[_.]@', $service_id);

    // Find the info for the requested service in our stored data.
    $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
    $services_data = $task_handler_report_services->listServiceData();

    $service_info = [];
    $service_info['id'] = $service_id;

    if (isset($services_data[$service_id])) {
      // Copy these explicitly for maintainability and readability.
      $service_info['description']  = $services_data[$service_id]['description'];
      $service_info['interface']    = $services_data[$service_id]['interface'];
    }
    else {
      throw new InvalidInputException("Service {$service_id} not found.");
    }

    // Derive further information.
    $service_info['variable_name'] = implode('_', $id_pieces);
    $service_info['property_name'] = CaseString::snake($service_info['variable_name'])->camel();

    // If the service has no interface, typehint on the class.
    $service_info['typehint'] = $service_info['interface'] ?? $service_info['class'];

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
