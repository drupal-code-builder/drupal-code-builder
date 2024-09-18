<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use CaseConverter\StringAssembler;
use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\File\DrupalExtension;
use DrupalCodeBuilder\Utility\NestedArray;
use Ckr\Util\ArrayMerger;
use MutableTypedData\Data\DataItem;

/**
 * Generator for a service.
 */
class Service extends PHPClassFileWithInjection implements AdoptableInterface {

  use NameFormattingTrait;

  /**
   * Define the component data this component needs to function.
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    // Create the presets definition for service tag type property.
    $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
    $service_types_data = $task_handler_report_services->listServiceTypeData();
    $presets = [];
    foreach ($service_types_data as $type_tag => $type_data) {
      // Form the suggested service name from the last portion of the tag, thus:
      // 'module_install.uninstall_validator' -> 'mymodule.uninstall_validator'
      $type_tag_pieces = explode('.', $type_tag);
      $service_name_suggestion = array_pop($type_tag_pieces);

      $presets[$type_tag] = [
        // Option label.
        'label' => $type_data['label'],
        'data' => [
          // Values that are forced on other properties.
          // These are set in the process stage.
          'force' => [
            // Name of another property => Value for that property.
            'interfaces' => [
              'value' => [
                '\\' . $type_data['interface'],
              ],
            ],
            'tags' => [
              'value' => [
                0 => [
                  // The preset option is the tag.
                  'name' => $type_tag,
                  'priority' => 0,
                ],
              ],
            ],
            // TODO: methods.
          ],
          // Values that are suggested for other properties.
          'suggest' => [
            /*
            // These don't do much yet -- UIs will need to handle these in 3.2.x
            'service_name' => [
              'value' => $service_name_suggestion,
            ],
            */
            // TODO: skip for now, until plain_class_name is a proper property!
            /*
            'service_class_name' => [
              // not just data -- data + processing instructions.

            ],
            */
          ],
        // states: TODO.
        ],
      ];
    }

    // Hardcoded extras for presets.
    // TODO: This is only wrapped because test data doesn't have the logger tag!
    if (isset($presets['logger'])) {
      // TODO: Add test coverage for this.
      $presets['logger']['data']['force']['service_name_prefix']['value'] = 'logger';
    }

    // TODO: implement this once we have a processing system.
    //$presets['event_subscriber']['data']['force']['relative_class_name'] ...

    uasort($presets, function($a, $b) {
      return strnatcasecmp($a['label'], $b['label']);
    });

    $properties = [
      'service_tag_type' => PropertyDefinition::create('string')
        ->setLabel('Service type')
        ->setDescription('Tags this service for a particular purpose and implements the interface.')
        ->setPresets($presets),
      'service_name' => PropertyDefinition::create('string')
        ->setLabel('Service name')
        ->setDescription("The name of the service, without the module name prefix.")
        ->setRequired(TRUE)
        ->setValidators('service_name'),
      'service_name_prefix' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setExpressionDefault("parent.root_component_name.get()"),
      'prefixed_service_name' => PropertyDefinition::create('string')
        ->setLabel('The plain class name, e.g. "MyClass"')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("parent.service_name_prefix.get() ~ '.' ~ parent.service_name.get()")
            ->setDependencies('..:root_component_name')
        ),
      // The parent service name.
      'parent' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      'tags' => PropertyDefinition::create('complex')
        ->setInternal(TRUE)
        ->setMultiple(TRUE)
        ->setProperties([
          'name' => PropertyDefinition::create('string')
            ->setRequired(TRUE),
          // TODO: rather than have to declare all of these, which is unscalable
          // as AFAIK tags can have any properties, this should be a new
          // associative array format type.
          'priority' => PropertyDefinition::create('string'),
          'applies_to' => PropertyDefinition::create('string'),
          'tag' => PropertyDefinition::create('string'),
          'call' => PropertyDefinition::create('string'),
        ]),
    ];

    // Put the parent definitions after ours.
    parent::addToGeneratorDefinition($definition);
    $parent_properties = $definition->getProperties();
    $properties += $parent_properties;
    $definition->setProperties($properties);

    // Use the plain class name as the exposed property.
    $definition->getProperty('plain_class_name')
      ->setInternal(TRUE)
      ->getDefault()
        ->setCallable([static::class, 'defaultPlainClassName'])
        ->setDependencies('..:service_name');

    $definition->getProperty('relative_class_name')
      ->setInternal(TRUE);

    $definition->getProperty('relative_namespace')
      ->setCallableDefault([static::class, 'defaultRelativeNamespace']);
  }

  public static function defaultPlainClassName($data_item) {
    // The service name is its ID as a service.
    // implode and ucfirst()
    $service_id = $data_item->getParent()->service_name->value;
    $service_id_pieces = preg_split('/[\._]/', $service_id);
    // Create an unqualified class name by turning this into pascal case.
    $plain_class_name = (new \CaseConverter\StringAssembler($service_id_pieces))->pascal();

    return $plain_class_name;
  }

  public static function defaultRelativeNamespace($data_item) {
    // Quick hack!
    // TODO remove once the processor system is done.
    if ($data_item->getParent()->service_tag_type->value == 'event_subscriber') {
      return 'EventSubscriber';
    }
    else {
      return $data_item->getItem('module:configuration:service_namespace')->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function findAdoptableComponents(DrupalExtension $extension): array {
    $services_filename = $extension->name . '.services.yml';
    if (!$extension->hasFile($services_filename)) {
      return [];
    }

    $yaml = $extension->getFileYaml($services_filename);
    $service_names = array_keys($yaml['services']);
    return array_combine($service_names, $service_names);
  }

  /**
   * {@inheritdoc}
   */
  public static function adoptComponent(DataItem $component_data, DrupalExtension $extension, string $property_name, string $name): void {
    $services_filename = $extension->name . '.services.yml';
    $yaml = $extension->getFileYaml($services_filename);
    $service_yaml = $yaml['services'][$name];

    // An adopted service migt not be in the standard namespace, so we need to
    // detect and specify that.
    $class_name_pieces = explode('\\', $service_yaml['class']);

    $value = [
      'service_name' => preg_replace("@^{$extension->name}\.@", '', $name),
      'injected_services' => array_map(fn ($service_name) => ltrim($service_name, '@'), $service_yaml['arguments']),
      // These properties are hidden in the UI but will be stored anyway.
      'plain_class_name' => end($class_name_pieces),
      'relative_namespace' => implode('\\', array_slice($class_name_pieces, 2, -1)),
    ];

    foreach ($component_data->getItem($property_name) as $delta => $delta_item) {
      if ($delta_item->service_name->value == $value['service_name']) {
        $merge_delta = $delta;
        break;
      }
    }

    if (isset($merge_delta)) {
      $existing_value = $component_data->getItem($property_name)[$merge_delta]->export();
      $merged_value = NestedArray::mergeDeep($existing_value, $value);

      $component_data->getItem($property_name)[$merge_delta]->set($merged_value);
    }
    else {
      // Bit of a WTF: this requires the Service class to know it's being used
      // as a multi-valued item in the Module generator.
      $item_data = $component_data->getItem($property_name)->createItem();
      $item_data->set($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function detectExistence(DrupalExtension $extension) {
    // We detect existence by looking in the services.yml file.
    $services_yml_filename = str_replace('%module', $this->component_data->root_component_name->value, '%module.services.yml');

    $service_file_path = (
      $this->component_data->component_base_path->value ?
      $this->component_data->component_base_path->value . '/' :
      ''
      )
      . $services_yml_filename;

    if (!$extension->hasFile($service_file_path)) {
      $this->exists = FALSE;
      return;
    }

    $services_yaml = $extension->getFileYaml($service_file_path);

    if (isset($services_yaml['services'][$this->component_data['prefixed_service_name']])) {
      $this->exists = TRUE;
      $this->extension = $extension;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getExistingInjectedServices(): array {
    // Statically cache as we come here several times.
    if (isset($this->existingServices)) {
      return $this->existingServices;
    }

    $this->existingServices = [];
    if ($this->exists) {
      $services_yml_filename = str_replace('%module', $this->component_data->root_component_name->value, '%module.services.yml');

      $service_file_path = (
        $this->component_data->component_base_path->value ?
        $this->component_data->component_base_path->value . '/' :
        ''
        )
        . $services_yml_filename;

      $services_yaml = $this->extension->getFileYaml($services_yml_filename);

      if (isset($services_yaml['services'][$this->component_data['prefixed_service_name']]['arguments'])) {
        foreach ($services_yaml['services'][$this->component_data['prefixed_service_name']]['arguments'] as $argument) {
          $service_id = ltrim($argument, '@');
          $this->existingServices[] = $service_id;
        }
      }
    }
    return $this->existingServices;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $yaml_data_arguments = [];

    $requested_services = [];
    foreach ($this->component_data['injected_services'] as $service_id) {
      $requested_services[] = $service_id;
    }

    $existing_services = $this->getExistingInjectedServices();

    // Use the ArrayMerger even though these are flat arrays, so we get the same
    // behaviour as the YAML data will in the YMLFile generator.
    // Put the existing services first.
    $merger = new ArrayMerger($existing_services, $requested_services);
    $merger->preventDoubleValuesWhenAppendingNumericKeys(TRUE);
    $service_ids = $merger->mergeData();

    $existing_only_services = array_diff($existing_services, $requested_services);

    // Existing services need a component too.
    foreach ($existing_only_services as $service_id) {
      $components['service_' . $service_id] = [
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
        'class_has_static_factory' => $this->hasStaticFactoryMethod,
        'class_has_constructor' => TRUE,
        'class_name' => $this->component_data->qualified_class_name->value,
      ];
    }

    // Rearrange the order of the generated services, so the existing ones go
    // first and in the existing order.
    $service_components = [];
    foreach ($service_ids as $service_id) {
      $service_components[$service_id] = $components['service_' . $service_id];
      unset($components['service_' . $service_id]);
    }

    foreach ($service_ids as $service_id) {
      // Put the service components in the right order.
      $components['service_' . $service_id] = $service_components[$service_id];

      // Add the service ID to the arguments in the YAML data.
      if (substr_count($service_id, ':') != 0) {
        $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
        $services_data = $task_handler_report_services->listServiceData();
        $real_service_id = $services_data[$service_id]['real_service'];

        if (in_array("@{$real_service_id}", $yaml_data_arguments)) {
          // Don't repeat it!
          continue;
        }

        $yaml_data_arguments[] = '@' . $real_service_id;
      }
      else {
        $yaml_data_arguments[] = '@' . $service_id;
      }
    }

    $yaml_service_definition = [
      'class' => $this->component_data['qualified_class_name'],
    ];
    if ($yaml_data_arguments) {
      $yaml_service_definition['arguments'] = $yaml_data_arguments;
    }

    // Service tags.
    if (isset($this->component_data['tags'])) {
      foreach ($this->component_data['tags'] as $tag_value) {
        $yaml_service_definition['tags'][] = $tag_value;
      }
    }

    // TODO: document and declare this property!
    if ($this->component_data->parent->value) {
      $yaml_service_definition['parent'] = $this->component_data->parent->value;
    }

    $yaml_data = [];
    $yaml_data['services'] = [
      $this->component_data['prefixed_service_name'] => $yaml_service_definition,
    ];

    if ($this->component_data->getItem('module:configuration:service_linebreaks')->value) {
      $line_break_between_blocks_level = 1;
    }
    else {
      $line_break_between_blocks_level = NULL;
    }

    $yaml_inline_levels = [
      // Expand the tags property further than the others.
      'tags' => [
        'address' => ['services', '*', 'tags'],
        'level' => 4,
      ],
    ];

    if ($this->component_data->getItem('module:configuration:service_parameters_linebreaks')->value) {
      $yaml_inline_levels += [
        'arguments' => [
          'address' => ['services', '*', 'arguments'],
          'level' => 4,
        ],
      ];
    }

    $components['%module.services.yml'] = [
      'component_type' => 'YMLFile',
      'filename' => '%module.services.yml',
      'yaml_data' => $yaml_data,
      'yaml_inline_level' => 3,
      'inline_levels_extra' => $yaml_inline_levels,
      'line_break_between_blocks_level' => $line_break_between_blocks_level,
    ];

    // Add methods from the tag type interface.
    if (!empty($this->component_data->service_tag_type->value)) {
      $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
      $service_types_data = $task_handler_report_services->listServiceTypeData();

      if (!empty($service_types_data[$this->component_data->service_tag_type->value]['methods'])) {
        $service_type_interface_data = $service_types_data[$this->component_data['service_tag_type']]['methods'];
        foreach ($service_type_interface_data as $method_name => $method_data) {
          $components['function-' . $method_name] = $this->createFunctionComponentFromMethodData($method_data);
        }
      }
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    parent::collectSectionBlocks();

    $this->collectSectionBlocksForDependencyInjection();
  }

}
