<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\InjectedService.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a service injection into a class.
 */
class InjectedService extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return $this->component_data['container'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
    $services_data = $task_handler_report_services->listServiceData();
    $service_data = $services_data[$this->component_data['service_id']];

    // TODO: use NameFormattingTrait methods here.
    $id_pieces = preg_split('@[_.]@', $service_data['id']);
    $service_data['variable_name'] = implode('_', $id_pieces);
    $id_pieces_first = array_shift($id_pieces);
    $service_data['property_name'] = implode('', array_merge([$id_pieces_first], array_map('ucfirst', $id_pieces)));
    $interface_pieces = explode('\\', $service_data['interface']);
    $service_data['unqualified_interface'] = array_pop($interface_pieces);

    return [
      'service' => [
        'role' => 'service',
        'content' => $service_data,
      ],
      'container_extraction' => [
        'role' => 'container_extraction',
        'content' => "\$container->get('{$service_data['id']}'),",
      ],
      'constructor_param' => [
        'role' => 'constructor_param',
        'content' => [
          'name'        => $service_data['variable_name'],
          'typehint'    => $service_data['interface'],
          'description' => $service_data['description'],
        ],
      ],
    ];
  }

}
