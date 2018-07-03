<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\StringAssembler;

/**
 * Generator for a service.
 */
class Service extends PHPClassFileWithInjection {

  use NameFormattingTrait;

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
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

    $data_definition = array(
      'service_tag_type' => [
        'label' => 'Service type preset',
        'presets' => $presets,
        // These are for the benefit of tests, as UIs will pass in an empty
        // value.
        'default' => '',
        'process_default' => TRUE,
      ],
      'service_name' => array(
        'label' => 'Service name',
        'description' => "The name of the service, without the module name prefix.",
        'required' => TRUE,
      ),
      'prefixed_service_name' => [
        'internal' => TRUE,
        'default' => function($component_data) {
          return $component_data['root_component_name'] . '.' . $component_data['service_name'];
        },
      ],
      'injected_services' => array(
        'label' => 'Injected services',
        'format' => 'array',
        'options' => function(&$property_info) {
          $mb_task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');

          $options = $mb_task_handler_report_services->listServiceNamesOptions();

          return $options;
        },
        'options_extra' => \DrupalCodeBuilder\Factory::getTask('ReportServiceData')->listServiceNamesOptionsAll(),
      ),
      'service_class_name' => [
        'computed' => TRUE,
        'format' => 'string',
        'default' => function($component_data) {
          // The service name is its ID as a service.
          // implode and ucfirst()
          $service_id = $component_data['service_name'];
          $service_id_pieces = preg_split('/[\._]/', $service_id);
          // Create an unqualified class name by turning this into pascal case.
          $plain_class_name = (new \CaseConverter\StringAssembler($service_id_pieces))->pascal();

          return $plain_class_name;
        },
      ],
    );

    // Put the parent definitions after ours.
    $data_definition += parent::componentDataDefinition();

    // Take the relative class name from the service class name.
    $data_definition['relative_class_name']['default'] = function($component_data) {
      // Services are typically in the module's top namespace.
      $service_class_name = $component_data['service_class_name'];

      // Quick hack!
      // TODO remove once the processor system is done.
      if ($component_data['service_tag_type'] == 'event_subscriber') {
        return ['EventSubscriber', $service_class_name];
      }

      return [$service_class_name];
    };

    return $data_definition;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = [];

    $yaml_data_arguments = [];
    foreach ($this->component_data['injected_services'] as $service_id) {
      $components['service_' . $service_id] = array(
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
      );

      // Add the service ID to the arguments in the YAML data.
      $yaml_data_arguments[] = '@' . $service_id;
    }

    $yaml_service_definition = [
      'class' => $this->component_data['qualified_class_name'],
    ];
    if ($yaml_data_arguments) {
      $yaml_service_definition['arguments'] = $yaml_data_arguments;
    }

    // Service tags.
    // TODO: document and declare this property!
    if (!empty($this->component_data['service_tag_type'])) {
      $yaml_service_definition['tags'][] = [
        // The preset option is the tag.
        'name' => $this->component_data['service_tag_type'],
        'priority' => 0,
      ];
    }

    // TODO: document and declare this property!
    if (isset($this->component_data['parent'])) {
      $yaml_service_definition['parent'] = $this->component_data['parent'];
    }

    $yaml_data = [];
    $yaml_data['services'] = [
      $this->component_data['prefixed_service_name'] => $yaml_service_definition,
    ];

    $components['%module.services.yml'] = [
      'component_type' => 'YMLFile',
      'filename' => '%module.services.yml',
      'yaml_data' => $yaml_data,
      'yaml_inline_level' => 3,
      // Expand the tags property further than the others.
      'inline_levels_extra' => [
        'tags' => [
          'address' => ['services', '*', 'tags'],
          'level' => 4,
        ],
      ],
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

    return array();
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    $this->collectSectionBlocksForDependencyInjection();

    // Add methods from the tag type interface.
    if (!empty($this->component_data['service_tag_type'])) {
      $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
      $service_types_data = $task_handler_report_services->listServiceTypeData();
      $service_type_interface_data = $service_types_data[$this->component_data['service_tag_type']]['methods'];
      $this->createBlocksFromMethodData($service_type_interface_data);
    }
  }

}
