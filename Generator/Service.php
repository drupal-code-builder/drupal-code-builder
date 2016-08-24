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

  /**
   * Constructor method; sets the component data.
   *
   * The component name is taken to be the service ID. KILL
   */
  function __construct($component_name, $component_data, $root_generator) {
    // Prefix the service name with the module name.
    $component_data['original_service_name'] = $component_data['service_name'];
    $component_data['service_name'] = $component_data['root_component_name'] . '.' . $component_data['service_name'];

    // The service name is its ID as a service.
    // implode and ucfirst()
    $service_id = $component_data['original_service_name'];
    $service_id_pieces = preg_split('/[\._]/', $service_id);
    // Create an unqualified class name by turning this into camel case.
    $unqualified_class_name = implode('', array_map('ucfirst', $service_id_pieces));
    // Form the full class name by adding a namespace Drupal\MODULE.
    $class_name_pieces = array(
      'Drupal',
      $component_data['root_component_name'],
      $unqualified_class_name,
    );
    $component_data['qualified_class_name'] = implode('\\', $class_name_pieces);

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * Define the component data this component needs to function.
   */
  protected static function componentDataDefinition() {
    return array(
      'service_name' => array(
        'label' => 'Service name',
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

    $yaml_data = [];
    $yaml_data['services'] = [
      $this->component_data['service_name'] => [
        'class' => $this->qualified_class_name,
        'arguments' => $yaml_data_arguments,
      ],
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
    // TEMPORARY, until Generate task handles returned contents.
    $this->injectedServices = $this->filterComponentContentsForRole($children_contents, 'service');

    $this->childContentsGrouped = $this->groupComponentContentsByRole($children_contents);

    return array();
  }

  /**
   * Return the body of the class's code.
   */
  function class_code_body() {
    $code = array();

    // Injected services.
    if (!empty($this->injectedServices)) {
      // Class properties.
      $code[] = '';

      foreach ($this->injectedServices as $service_info) {
        $var_doc = $this->docBlock([
          $service_info['description'] . '.',
          '',
          '@var ' . $service_info['interface']
        ]);
        $code = array_merge($code, $var_doc);
        $code[] = 'protected $' . $service_info['property_name'] . ';';
        $code[] = '';
      }

      // __construct() method
      $code = array_merge($code, $this->codeBodyClassMethodConstruct());
    }

    // Indent all the class code.
    // TODO: is there a nice way of doing indents?
    $code = array_map(function ($line) {
      return empty($line) ? $line : '  ' . $line;
    }, $code);

    return $code;
  }

  /**
   * Creates the code lines for the __construct() method.
   */
  protected function codeBodyClassMethodConstruct() {
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
    $code[] = '';
    return $code;
  }

}
