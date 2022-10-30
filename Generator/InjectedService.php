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
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'service_id' => PropertyDefinition::create('string')
        ->setLabel('Service name')
        ->setRequired(TRUE),
      'service_info' => PropertyDefinition::create('mapping')
        ->setDefault(DefaultDefinition::create()
          ->setCallable([static::class, 'defaultServiceInfo'])
          ->setDependencies('..:service_id')
      ),
      'class_has_static_factory' => PropertyDefinition::create('boolean')
        ->setLabel('Whether the class injecting this uses a static create() method.')
        ->setRequired(TRUE),
      // Allows special cases for assignment in the construct method.
      // So far only skips assignment if set to anything! TODO!!
      'assignment' => PropertyDefinition::create('string'),
      'class_name' => PropertyDefinition::create('string'),
    ]);

    return $definition;
  }

  public static function defaultServiceInfo($data_item) {
    $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
    $services_data = $task_handler_report_services->listServiceData();

    // Build up the service info.
    $service_info = [];
    $service_info['id'] = $service_id = $data_item->getParent()->service_id->value;
    $service_info['type']         = 'service';

    // Copy these explicitly for maintainability and readability.
    $service_info['label']          = $services_data[$service_id]['label'];
    $service_info['variable_name']  = $services_data[$service_id]['variable_name'];
    $service_info['description']    = $services_data[$service_id]['description'];
    $service_info['interface']      = $services_data[$service_id]['interface'];
    $service_info['class']          = $services_data[$service_id]['class'] ?? '';
    $service_info['property_name']  = CaseString::snake($service_info['variable_name'])->camel();

    if (substr_count($service_id, ':') != 0) {
      // If the service name contains a ':' then it's a pseudoservice, that
      // is, not an actual service but something else injectable.
      [$pseudo_service_type, $variant] = explode(':', $service_id);

      $service_info['type']           = 'pseudoservice';
      $service_info['variant']        = $variant;
      $service_info['real_service']   = $services_data[$service_id]['real_service'];
      $service_info['service_method'] = $services_data[$service_id]['service_method'];

      $real_service_info = $services_data[$services_data[$service_id]['real_service']];
      // Argh too many properties!
      $service_info['real_service_variable_name'] = $real_service_info['variable_name'];
      $service_info['real_service_typehint']      = $real_service_info['interface'] ?: $real_service_info['class'];
      $service_info['real_service_description']   = $real_service_info['description'];
    }

    // If the service has no interface, typehint on the class.
    $service_info['typehint'] = $service_info['interface'] ?: $service_info['class'];
    // dump($service_info);

    return $service_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return $this->component_data['containing_component'] . '-' . $this->component_data['service_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $service_info = $this->component_data->service_info->value;

    $components['constructor_param'] = [
      'component_type' => 'PHPFunctionParameter',
      'containing_component' => '%requester:%requester:construct',
      'parameter_name' => $service_info['variable_name'],
      'typehint' => $service_info['typehint'],
      'description' => $service_info['description'] . '.',
      'class_name' => $this->component_data->class_name->value,
      'method_name' => '__construct',
    ];

    $service_type = (substr_count($service_info['id'], ':') == 0) ? 'service' : 'pseudoservice';

    if ($this->component_data->assignment->isEmpty()) {
      if ($service_info['type'] == 'service') {
        $code_line = "\$this->{$service_info['property_name']} = \${$service_info['variable_name']};";
      }
      else {
        // Pseudoservice: needs to be extracted from a real service.
        if ($this->component_data->class_has_static_factory->value) {
          // The static factory method has got the pseudoservice object from the
          // real service, and passes it to the constructor.
          $code_line = "\$this->{$service_info['property_name']} = \${$service_info['variable_name']};";
        }
        else {
          // There is no static factory, so the constructor receives the real
          // service. We have to extract it here.
          $code_line = "\$this->{$service_info['property_name']} = \${$service_info['real_service_variable_name']}->{$service_info['service_method']}('{$service_info['variant']}');";

          // Also, the constructor parameter is the real service, not the
          // pseudoservice.
          $components['constructor_param']['parameter_name'] = $service_info['real_service_variable_name'];
          $components['constructor_param']['typehint'] = $service_info['real_service_typehint'];
          $components['constructor_param']['description'] = $service_info['real_service_description'] . '.';
        }
      }
    }

    if (isset($code_line)) {
      $components['constructor_line'] = [
        'component_type' => 'PHPFunctionLine',
        'containing_component' => '%requester:%requester:construct',
        'code' => $code_line,
      ];
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'injected_service';
  }

  /**
   * Gets the contents of the component.
   *
   * @return array
   *   An array of different elements, keyed by role.
   */
  public function getContents(): array {
    $service_info = $this->component_data['service_info'];

    $service_type = (substr_count($service_info['id'], ':') == 0) ? 'service' : 'pseudoservice';

    if ($service_info['type'] == 'service') {
      $container_extraction = "\$container->get('{$service_info['id']}'),";

      $property_assignment = [
        'id'            => $service_info['id'],
        'property_name' => $service_info['property_name'],
        'variable_name' => $service_info['variable_name'],
      ];
    }
    else {
      // Pseudoservice: needs to be extracted from a real service.
      $container_extraction = "\$container->get('{$service_info['real_service']}')->{$service_info['service_method']}('{$service_info['variant']}'),";

      $property_assignment = [
        'id'            => $service_info['id'],
        'property_name' => $service_info['property_name'],
        'variable_name' => $service_info['variable_name'],
        'parameter_extraction' => "{$service_info['real_service_variable_name']}->{$service_info['service_method']}('{$service_info['variant']}')",
      ];
    }

    $contents = [
      'service' => $service_info,
      'service_property' => [
        'id'            => $service_info['id'],
        'property_name' => $service_info['property_name'],
        'typehint'      => $service_info['typehint'],
        'description'   => $service_info['description'],
      ],
      'container_extraction' => $container_extraction,
      'constructor_param' => [
        // At this point, we can't know whether the service is being injected
        // into a class that has a static factory or not, so we don't know if
        // the constructor param is:
        // a. the pseudoservice variable name (because the factory method did
        //  the extraction)
        // b. the real service variable name (because the constructor then does
        //  the extraction)
        'id'          => $service_info['id'],
        'name'        => $service_info['variable_name'],
        'typehint'    => $service_info['typehint'],
        'description' => $service_info['description'] . '.',
        'type'        => $service_type,
        'real_name'   => $service_info['real_service_variable_name'] ?? '',
        'real_typehint'    => $service_info['real_service_typehint'] ?? '',
        'real_description' => ($service_info['real_service_description'] ?? '') . '.',
        // 'container_extraction' => $container_extraction,
      ],
      'property_assignment' => $property_assignment,
    ];

    return $contents;
  }

}
