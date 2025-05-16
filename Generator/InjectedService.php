<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for a service injection into a class.
 *
 * TODO: probably no longer needs to have a containing component?
 */
class InjectedService extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'service_id' => PropertyDefinition::create('string')
        ->setLabel('Service name')
        ->setRequired(TRUE),
      'decorated' => PropertyDefinition::create('boolean')
        ->setLiteralDefault(FALSE),
      'service_info' => PropertyDefinition::create('mapping')
        ->setDefault(DefaultDefinition::create()
          ->setCallable([static::class, 'defaultServiceInfo'])
          ->setDependencies('..:service_id')
      ),
      'class_has_constructor' => PropertyDefinition::create('boolean')
        ->setLabel('Whether the class injecting this uses a constructor method.')
        ->setRequired(TRUE),
      'class_has_static_factory' => PropertyDefinition::create('boolean')
        ->setLabel('Whether the class injecting this uses a static create() method.')
        ->setRequired(TRUE),
      // Allows special cases for assignment in the construct method.
      'omit_assignment' => PropertyDefinition::create('boolean'),
      'class_name' => PropertyDefinition::create('string'),
    ]);
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

    if ($data_item->getParent()->decorated->value) {
      $service_info['variable_name'] = 'decorated';
      $service_info['property_name'] = 'decorated';
      $service_info['description'] = 'The decorated ' . $services_data[$service_id]['label'];
    }

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

    if ($this->component_data->class_has_constructor->value) {
      $components['constructor_set_property'] = [
        'component_type' => 'PHPClassConstructorSetProperty',
        // We expect the requesting PHP class generator to have requested a
        // constructor.
        'containing_component' => '%requester:%requester:construct',
        'class_name' => $this->component_data->class_name->value,
        'property_name' => $service_info['property_name'],
        'parameter_name' => $service_info['variable_name'],
        'type' => $service_info['typehint'],
        'description' => $service_info['description'] . '.',
        'omit_assignment' => $this->component_data->omit_assignment->value,
      ];

      // Determine whether the constructor property needs an expression.
      if ($this->component_data->omit_assignment->isEmpty()
        && $service_info['type'] != 'service'
        && !$this->component_data->class_has_static_factory->value
      ) {
        // With a pseudoservice and no static factory, the constructor receives
        // the real service. We have to extract it in the constructor body.
        $components['constructor_set_property']['expression'] = "£{$service_info['real_service_variable_name']}->{$service_info['service_method']}('{$service_info['variant']}')";

        // Also, the constructor parameter is the real service, not the
        // pseudoservice.
        $components['constructor_set_property']['parameter_name'] = $service_info['real_service_variable_name'];
        $components['constructor_set_property']['type'] = $service_info['real_service_typehint'];
        $components['constructor_set_property']['description'] = $service_info['real_service_description'] . '.';

        $components['constructor_set_property']['property_description'] = $service_info['description'] . '.';
        $components['constructor_set_property']['property_type'] = $service_info['typehint'];
      }
    }

    if ($this->component_data->class_has_static_factory->value) {
      if ($service_info['type'] == 'service') {
        $container_extraction = "\$container->get('{$service_info['id']}'),";
      }
      else {
        // Pseudoservice: needs to be extracted from a real service.
        $container_extraction = "\$container->get('{$service_info['real_service']}')->{$service_info['service_method']}('{$service_info['variant']}'),";
      }

      // Functions lines for the 'create' method get put inside the static
      // create call: see PHPClassFileWithInjection.
      $components['create_line'] = [
        'component_type' => 'PHPFunctionBodyLines',
        'containing_component' => '%requester:%requester:create',
        'code' => '  ' . $container_extraction,
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
