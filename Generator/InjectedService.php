<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Exception\InvalidInputException;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use CaseConverter\StringAssembler;
use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;

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
    ];

    $data_definition['service_info'] = PropertyDefinition::create('mapping')
      ->setDefault(DefaultDefinition::create()
        ->setLazy(TRUE)
        ->setCallable([static::class, 'defaultServiceInfo'])
        ->setDependencies('..:TODO')
    );

    // Bit of a hack for PHPUnitTest generator's sake. Lets the requesting
    // generator tack a suffix onto the roles we give to component contents.
    // PHPUnitTest needs this as it has two kinds of service.
    $data_definition['role_suffix'] = [
      'internal' => TRUE,
    ];

    return $data_definition;
  }

  public static function defaultServiceInfo($data_item) {
    $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
    $services_data = $task_handler_report_services->listServiceData();

    // Build up the service info.
    $service_info = [];
    $service_info['id'] = $service_id = $data_item->getParent()->service_id->value;
    $service_info['type']         = 'service';

    // Copy these explicitly for maintainability and readability.
    $service_info['label']        = $services_data[$service_id]['label'];
    $service_info['description']  = $services_data[$service_id]['description'];
    $service_info['interface']    = $services_data[$service_id]['interface'];
    $service_info['class']        = $services_data[$service_id]['class'] ?? '';

    // Derive variable and property names.
    // TODO: move this to the analysis instead.
    $service_id_pieces = preg_split('@[_.]@', $service_id);

    if (substr_count($service_id, ':') != 0) {
      // If the service name contains a ':' then it's a pseudoservice, that
      // is, not an actual service but something else injectable.
      [$pseudo_service_type, $variant] = explode(':', $service_id);

      $service_info['type'] = 'storage';

      if ($pseudo_service_type != 'storage') {
        throw new InvalidInputException("Pseudoservice {$service_id} not found.");
      }

      $service_info['variant']        = $variant;
      $service_info['variable_name']  = $variant . '_storage';
      $service_info['property_name']  = CaseString::snake($variant)->camel() . 'Storage';
    }
    elseif (substr_count($service_id, '.') == 0) {
      // If the service name does not contain any dots, in particular,
      // 'current_user', then use that, as it's usually clearer than the
      // class name.
      $service_info['variable_name'] = (new StringAssembler($service_id_pieces))->snake();
      $service_info['property_name'] = (new StringAssembler($service_id_pieces))->camel();
    }
    else {
      // Otherwise, use the service class name.
      $class_pieces = explode('\\', $services_data[$service_id]['class']);
      $short_class = array_pop($class_pieces);

      // Trim an 'Interface' suffix from the class in case it's actually an
      // interface. This is the case for cache backend services.
      $short_class = preg_replace('@Interface$@', '', $short_class);

      $service_info['variable_name'] = CaseString::pascal($short_class)->snake();
      $service_info['property_name'] = CaseString::pascal($short_class)->camel();
    }

    // If the service has no interface, typehint on the class.
    $service_info['typehint'] = $service_info['interface'] ?: $service_info['class'];

    return $service_info;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $service_info = $this->component_data['service_info'];

    if ($service_info['type'] == 'service') {
      $container_extraction = "\$container->get('{$service_info['id']}'),";
    }
    else {
      $container_extraction = "\$container->get('entity_type.manager')->getStorage('{$service_info['variant']}'),";
    }

    $contents = [
      'service' => [
        'role' => 'service',
        'content' => $service_info,
      ],
      'service_property' => [
        'role' => 'service_property',
        'content' => [
          'id'            => $service_info['id'],
          'property_name' => $service_info['property_name'],
          'typehint'      => $service_info['typehint'],
          'description'   => $service_info['description'],
        ],
      ],
      'container_extraction' => [
        'role' => 'container_extraction',
        'content' => $container_extraction,
      ],
      'constructor_param' => [
        'role' => 'constructor_param',
        'content' => [
          'id'          => $service_info['id'],
          'name'        => $service_info['variable_name'],
          'typehint'    => $service_info['typehint'],
          'description' => $service_info['description'] . '.',
        ],
      ],
      'property_assignment' => [
        'role' => 'property_assignment',
        'content' => [
          'id'            => $service_info['id'],
          'property_name' => $service_info['property_name'],
          'variable_name' => $service_info['variable_name'],
        ],
      ],
    ];

    if (!empty($this->component_data['role_suffix'])) {
      foreach ($contents as &$data) {
        $data['role'] .= $this->component_data['role_suffix'];
      }
    }

    return $contents;
  }

}
