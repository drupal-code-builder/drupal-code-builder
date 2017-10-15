<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Service.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a service.
 */
class Service extends PHPClassFile {

  use NameFormattingTrait;

  /**
   * Constructor method; sets the component data.
   *
   * The component name is taken to be the service ID. KILL
   */
  function __construct($component_name, $component_data, $root_generator) {
    // TODO: use computed properties for these.
    if (empty($component_data['prefixed_service_name'])) {
      // Prefix the service name with the module name.
      $component_data['prefixed_service_name'] = $component_data['root_component_name'] . '.' . $component_data['service_name'];
    }

    // Allow components to specify a plain class name (or indeed, cheat, and
    // specify namespaces that come beneath the module namespace).
    if (empty($component_data['plain_class_name']) && empty($component_data['relative_class_name'])) {
      // The service name is its ID as a service.
      // implode and ucfirst()
      $service_id = $component_data['service_name'];
      $service_id_pieces = preg_split('/[\._]/', $service_id);
      // Create an unqualified class name by turning this into camel case.
      $component_data['plain_class_name'] = implode('', array_map('ucfirst', $service_id_pieces));

      $component_data['relative_class_name'] = [$component_data['plain_class_name']];
    }
    else {
      $component_data['plain_class_name'] = end($component_data['relative_class_name']);
    }

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + array(
      'service_name' => array(
        'label' => 'Service name',
        'description' => "The name of the service, without the module name prefix.",
        'required' => TRUE,
      ),
      'injected_services' => array(
        'label' => 'Injected services',
        'format' => 'array',
        'options' => function(&$property_info) {
          $mb_task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');

          $options = $mb_task_handler_report_services->listServiceNamesOptions();

          return $options;
        },
        'options_allow_other' => TRUE,
      ),
    );
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = [];

    $yaml_data_arguments = [];
    foreach ($this->component_data['injected_services'] as $service_id) {
      // TODO: UPDATE!
      $components[$this->name . '_' . $service_id] = array(
        'component_type' => 'InjectedService',
        'container' => $this->getUniqueID(),
        'service_id' => $service_id,
      );

      // Add the service ID to the arguments in the YAML data.
      $yaml_data_arguments[] = '@' . $service_id;
    }

    $yaml_service_definition = [
      'class' => $this->qualified_class_name,
    ];
    if ($yaml_data_arguments) {
      $yaml_service_definition['arguments'] = $yaml_data_arguments;
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
      'yaml_data' => $yaml_data,
    ];

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function buildComponentContents($children_contents) {
    parent::buildComponentContents($children_contents);

    // TEMPORARY, until Generate task handles returned contents.
    $this->injectedServices = $this->filterComponentContentsForRole($children_contents, 'service');

    $this->childContentsGrouped = $this->groupComponentContentsByRole($children_contents);

    return array();
  }

  /**
   * Return the body of the class's code.
   */
  function classCodeBody() {
    // TODO: this code sets up class properties for the parent classCodeBody()
    // to work with, as if they had been set by buildComponentContents().
    // This should be refactored in due course.
    // Injected services.
    if (!empty($this->injectedServices)) {
      foreach ($this->injectedServices as $service_info) {
        $property_code = $this->docBlock([
          $service_info['description'] . '.',
          '',
          '@var ' . $service_info['interface']
        ]);
        $property_code[] = 'protected $' . $service_info['property_name'] . ';';

        $this->properties[] = $property_code;
      }

      // __construct() method
      $this->constructor = $this->codeBodyClassMethodConstruct();
    }

    return parent::classCodeBody();
  }

  /**
   * Creates the code lines for the __construct() method.
   */
  protected function codeBodyClassMethodConstruct() {
    if (empty($this->injectedServices)) {
      return [];
    }

    $parameters = [];
    foreach ($this->childContentsGrouped['constructor_param'] as $service_parameter) {
      $parameters[] = $service_parameter;
    }

    $code = $this->buildMethodHeader(
      '__construct',
      $parameters,
      [
        'docblock_first_line' => "Constructs a new {$this->plain_class_name}.",
        'prefixes' => ['public'],
      ]
    );

    foreach ($this->injectedServices as $service_info) {
      $code[] = "  \$this->{$service_info['property_name']} = \${$service_info['variable_name']};";
    }
    $code[] = '}';

    return $code;
  }

}
