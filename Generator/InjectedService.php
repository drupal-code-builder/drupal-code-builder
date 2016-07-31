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

  use NameFormattingTrait;

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
    $service_id = $this->component_data['service_id'];
    $id_pieces = preg_split('@[_.]@', $service_id);

    // First see if the requested service is in our stored data.
    // (TODO: store data on ALL services -- but need to figure out a way to
    // not give the user all ~500 in the options list!)
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
      // We don't have stored data about this service. Derive it from the actual
      // service instead.
      // Get the interface for the service.
      $service = \Drupal::service($service_id);
      $reflection = new \ReflectionClass($service);
      // This can get us more than one interface, if the declared interface has
      // parents. We only want one, so we need to go hacking in the code itself.
      $file = $reflection->getFileName();
      $start_line = $reflection->getStartLine();
      $spl = new \SplFileObject($file);
      $spl->seek($start_line - 1);
      $service_declaration_line = $spl->current();

      // Extract the interface from the declaration. We expect a service class
      // to only name one interface in its declaration!
      $matches = [];
      preg_match("@implements (\w+)@", $service_declaration_line, $matches);
      $interface_short_name = $matches[1];

      // Find the fully-qualified interface name in the array of interfaces
      // obtained from the reflection.
      $interfaces = $reflection->getInterfaces();
      foreach (array_keys($interfaces) as $interface) {
        if (substr($interface, -(strlen($interface_short_name) + 1)) == '\\' . $interface_short_name) {
          // The reflection doesn't give the initial '\'.
          $full_interface_name = '\\' . $interface;
        }
      }

      $service_label = implode(' ', $id_pieces);
      $service_info['description']  = "The $service_label service";
      $service_info['interface']    = $full_interface_name;
    }

    // Derive further information.
    $service_info['variable_name'] = implode('_', $id_pieces);
    $service_info['property_name'] = lcfirst($this->toCamel($service_info['variable_name']));

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
          'typehint'    => $service_info['interface'],
          'description' => $service_info['description'] . '.',
        ],
      ],
    ];
  }

}
