<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\Docblock;

/**
 * Generator for PHP class files that have services injected.
 */
class PHPClassFileWithInjection extends PHPClassFile {

  /**
   * The interface to use for the static create() method's container parameter.
   *
   * @var string
   */
  protected string $containerInterface = '\\Symfony\\Component\\DependencyInjection\\ContainerInterface';

  /**
   * Forces the requesting of a constructor method component.
   *
   * If FALSE, a constructor is only requested if there are injected services.
   *
   * @var bool
   */
  protected $forceConstructComponent = FALSE;

  /**
   * Static cache of services detected in an existing copy of this class.
   *
   * @var array
   */
  protected array $existingServices;

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $properties = [
      // Whether this class needs a create() static factory method.
      'use_static_factory_method' => PropertyDefinition::create('boolean')
        ->setInternal(TRUE)
        ->setLiteralDefault(FALSE),
      'injected_services' => PropertyDefinition::create('string')
        ->setLabel('Injected services')
        ->setDescription("Services to inject. Additionally, use 'storage:TYPE' to inject entity storage handlers.")
        ->setMultiple(TRUE)
        ->setOptionSetDefinition(\DrupalCodeBuilder\Factory::getTask('ReportServiceData')),
    ];

    $definition->addProperties($properties);
  }

  /**
   * Get any existing services from the existing class, if any.
   *
   * @return array
   *   A numeric array of service names.
   */
  protected function getExistingInjectedServices(): array {
    // We only support this for services so far, but this base class needs to be
    // aware.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    if (!$this->component_data->injected_services->isEmpty() || $this->getExistingInjectedServices() || $this->forceConstructComponent) {
      foreach ($this->component_data->injected_services->values() as $service_id) {
        $components['service_' . $service_id] = [
          'component_type' => 'InjectedService',
          'containing_component' => '%requester',
          'service_id' => $service_id,
          'class_has_static_factory' => $this->component_data->use_static_factory_method->value,
          'class_has_constructor' => TRUE,
          'class_name' => $this->component_data->qualified_class_name->value,
        ];
      }

      // The static factory create() method.
      if ($this->component_data->use_static_factory_method->value) {
        $create_parameters = [
          [
            'name' => 'container',
            'typehint' => $this->containerInterface,
          ],
        ];

        $base_create_parameters = $this->getCreateParameters();
        $create_parameters = array_merge($create_parameters, $base_create_parameters);


        // The create() factory method's code consists of a single statement,
        // the return of the object created with 'new static()'. The arguments
        // of this call are in three groups:
        // - the parameters which the base class expects for its constructor,
        //   e.g. PluginBase.
        // - services extracted from the container for parent classes, e.g. the
        //   plugin type base class.
        // - services requested by the component data for this class.
        $static_call_lines = [];

        $construct_base_arguments = $this->getConstructBaseParameters();
        foreach ($construct_base_arguments as $parameter) {
          if (isset($parameter['extraction'])) {
            // Some fixed parameters have an extraction of sorts, as they are values
            // from the plugin $configuration array, therefore an expression is
            // passed to the call rather than a variable.
            $static_call_lines[] = $parameter['extraction'] . ',';
          }
          else {
            $static_call_lines[] = '$' . $parameter['name'] . ',';
          }
        }

        $parent_injected_services = $this->getConstructParentInjectedServices();
        foreach ($parent_injected_services as $parent_container_extraction) {
          $static_call_lines[] = $parent_container_extraction['extraction'] . ',';
        }

        $create_body = [];
        $create_body[] = 'return new static(';
        foreach ($static_call_lines as $line) {
          $create_body[] = '  ' . $line;
        }
        // Parameters from requested services will go here. Each one gets a
        // terminal comma, but a trailing comma in a function call is fine since
        // PHP 7.3 and will likely be adopted as a Drupal coding standard (see
        // https://www.drupal.org/project/coding_standards/issues/2707507) and
        // so dealing with removing it dynamically is not worth the faff.
        $create_body[] = 'CONTAINED_COMPONENTS';
        $create_body[] = ');';

        $components['create'] = [
          'component_type' => 'PHPFunction',
          'function_name' => 'create',
          'containing_component' => '%requester',
          'docblock_inherit' => TRUE,
          'prefixes' => ['public', 'static'],
          'parameters' => $create_parameters,
          'body' => $create_body,
        ];
      }

      // The __construct() method. The parameters to this are the base parameter
      // + the parent injected services + our injected services.
      $base_parameters = $this->getConstructBaseParameters();
      $parent_injected_services = $this->getConstructParentInjectedServices();

      $parameters = [];
      $parameters = array_merge($parameters, $base_parameters);
      $parameters = array_merge($parameters, $parent_injected_services);

      $body = [];
      // Parent call line.
      if ($base_parameters || $parent_injected_services) {
        $parent_call_args = [];

        foreach ($base_parameters as $parameter) {
          $parent_call_args[] = '$' . $parameter['name'];
        }

        foreach ($parent_injected_services as $parameter) {
          $parent_call_args[] = '$' . $parameter['name'];
        }

        $body[] = 'parent::__construct(' . implode(', ', $parent_call_args) . ');';
      }

      // Remove keys which don't have data properties.
      foreach ($parameters as &$parameter) {
        if (isset($parameter['extraction'])) {
          unset($parameter['extraction']);
        }
      }

      // Parameters and body are supplied by components requested by
      // the InjectedService component.
      $components['construct'] = [
        'component_type' => 'PHPFunction',
        'function_name' => '__construct',
        'containing_component' => '%requester',
        'function_docblock_lines' => ["Creates a {$this->component_data->plain_class_name->value} instance."],
        'prefixes' => ['public'],
        // We want the __construct() method declaration's parameter to be
        // broken over multiple lines for legibility.
        // This is a Drupal coding standard still under discussion: see
        // https://www.drupal.org/node/1539712.
        'break_declaration' => TRUE,
        'parameters' => $parameters,
        'body' => $body,
      ];
    }

    return $components;
  }

  /**
   * The parameters for the base class.
   *
   * These parameters are passed to create() after the container, and then
   * passed on to __construct() and __construct()'s parent call.
   *
   * @return array
   */
  protected function getConstructBaseParameters() {
    return [];
  }

  /**
   * Returns the services injected into the parent class.
   *
   * In a class which injects its own services, services for the parent class
   * need to be extracted in the overridden create() method, and received by the
   * __construct() method, then passed to the parent implementation of
   * __construct(). They do not however need to be declared as class properties,
   * or set on the class, as that happens in the parent class.
   *
   * @return array
   *   A numeric array of the parameters for injected services that need to be passed up to
   *   the parent class. Each item is an array of data for one parameter, and
   *   contains:
   *   - 'name': The name for the variable, without the initial '$'.
   *   - 'description': The description for the parameter documentation; used
   *     for the __construct() method documentation.
   *   - 'typehint': The typehint, with the leading '\'.
   *   - 'extraction': The code that create() needs to use to get the service.
   *     Typically, this will be a call to $container->get(), but in some cases
   *     this has a chained call, e.g. to get a storage handler from the entity
   *     type manager service.
   */
  protected function getConstructParentInjectedServices() {
    return [];
  }

  /**
   * The parameters for the create() method.
   *
   * @return array
   */
  protected function getCreateParameters() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    parent::collectSectionBlocks();

    $this->collectSectionBlocksForDependencyInjection();
  }

  /**
   * Helper for collectSectionBlocks().
   */
  protected function collectSectionBlocksForDependencyInjection() {
    // Injected services.
    if (isset($this->containedComponents['injected_service'])) {
      // Service class property.
      foreach ($this->getContentsElement('service_property') as $service_property) {
        $docblock = DocBlock::property();
        $docblock[] = $service_property['description'] . '.';
        $docblock->var($service_property['typehint']);

        $property_code = $docblock->render();
        $property_code[] = 'protected $' . $service_property['property_name'] . ';';

        $this->properties[] = $property_code;
      }
    }
  }

}
