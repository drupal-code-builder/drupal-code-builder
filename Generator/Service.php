<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\StringAssembler;
use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for a service.
 */
class Service extends PHPClassFileWithInjection {

  use NameFormattingTrait;

  /**
   * Define the component data this component needs to function.
   */
  public static function getPropertyDefinition(): PropertyDefinition {
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
      'prefixed_service_name' => PropertyDefinition::create('string')
        ->setLabel('The plain class name, e.g. "MyClass"')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("parent.root_component_name.get() ~ '.' ~ parent.service_name.get()")
            ->setDependencies('..:root_component_name')
        ),
      'injected_services' => PropertyDefinition::create('string')
        ->setLabel('Injected services')
        ->setDescription("Services to inject. Additionally, use 'storage:TYPE' to inject entity storage handlers.")
        ->setMultiple(TRUE)
        ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportServiceData')),
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
    $definition = parent::getPropertyDefinition();
    $parent_properties = $definition->getProperties();
    $properties += $parent_properties;
    $definition->setProperties($properties);

    // Use the plain class name as the exposed property.
    // TODO: allow a relative namespace to come from a setting, so that, for
    // example, all services can be put in the \Service namespace.
    $definition->getProperty('plain_class_name')
      ->setInternal(TRUE)
      ->getDefault()
        ->setCallable([static::class, 'defaultPlainClassName'])
        ->setDependencies('..:service_name');

    $definition->getProperty('relative_class_name')
      ->setInternal(TRUE);

    $definition->getProperty('relative_namespace')
      ->setCallableDefault([static::class, 'defaultRelativeNamespace']);

    return $definition;
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
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $components = [];

    $yaml_data_arguments = [];
    foreach ($this->component_data['injected_services'] as $service_id) {
      $components['service_' . $service_id] = [
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
      ];

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

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    parent::buildComponentContents($children_contents);

    // TEMPORARY, until Generate task handles returned contents.
    $this->injectedServices = $this->filterComponentContentsForRole($children_contents, 'service');

    $this->childContentsGrouped = $this->groupComponentContentsByRole($children_contents);

    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    parent::collectSectionBlocks();

    $this->collectSectionBlocksForDependencyInjection();

    // Add methods from the tag type interface.
    if (!empty($this->component_data['service_tag_type'])) {
      $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
      $service_types_data = $task_handler_report_services->listServiceTypeData();

      if (!empty($service_types_data[$this->component_data['service_tag_type']]['methods'])) {
        $service_type_interface_data = $service_types_data[$this->component_data['service_tag_type']]['methods'];
        $this->createBlocksFromMethodData($service_type_interface_data);
      }
    }
  }

}
