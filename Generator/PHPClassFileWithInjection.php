<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for PHP class files that have services injected.
 */
class PHPClassFileWithInjection extends PHPClassFile {

  /**
   * Sets whether this class needs a create() static factory method.
   *
   * @var bool
   */
  protected $hasStaticFactoryMethod = FALSE;

  /**
   * An array of data about injected services.
   */
  protected $injectedServices = [];

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
   * Helper for collectSectionBlocks().
   */
  protected function collectSectionBlocksForDependencyInjection() {
    // Injected services.
    if (!empty($this->injectedServices)) {
      // Service class property.
      if (isset($this->childContentsGrouped['service_property'])) {
        foreach ($this->childContentsGrouped['service_property'] as $service_property) {
          $property_code = $this->docBlock([
            $service_property['description'] . '.',
            '',
            '@var ' . $service_property['typehint']
          ]);
          $property_code[] = 'protected $' . $service_property['property_name'] . ';';

          $this->properties[] = $property_code;
        }
      }

      // __construct() method
      $this->constructor = $this->codeBodyClassMethodConstruct();

      if ($this->hasStaticFactoryMethod) {
        // create() method.
        // Function data has been set by buildComponentContents(). WHAT DO I MEAN? TODO?
        // Goes first in the functions.
        $this->functions = array_merge([$this->codeBodyClassMethodCreate()], $this->functions);
      }
    }
  }

  /**
   * Creates the code lines for the create() method.
   */
  protected function codeBodyClassMethodCreate() {
    $parameters = [
      [
        'name' => 'container',
        'typehint' => '\\Symfony\\Component\\DependencyInjection\\ContainerInterface',
      ],
    ];

    $base_create_parameters = $this->getCreateParameters();
    $parameters = array_merge($parameters, $base_create_parameters);

    $code = $this->buildMethodHeader(
      'create',
      $parameters,
      [
        'inheritdoc' => TRUE,
        'prefixes' => ['public', 'static'],
      ]
    );

    $code[] = '  return new static(';

    // The create() factory method's code consists of a single statement, the
    // return of the object created with 'new static()'.
    // The arguments of this call are in three groups:
    // - the parameters which the base class expects for its constructor, e.g.
    //   PluginBase.
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
        $static_call_lines[] = '    ' . $parameter['extraction'] . ',';
      }
      else {
        $static_call_lines[] = '    ' . '$' . $parameter['name'] . ',';
      }
    }

    $parent_injected_services = $this->getConstructParentInjectedServices();
    foreach ($parent_injected_services as $parent_container_extraction) {
      $static_call_lines[] = '    ' . $parent_container_extraction['extraction'] . ',';
    }

    foreach ($this->childContentsGrouped['container_extraction'] as $container_extraction) {
      $static_call_lines[] = '    ' . $container_extraction;
    }

    // Remove the last comma.
    end($static_call_lines);
    $last_line_key = key($static_call_lines);
    $static_call_lines[$last_line_key] = rtrim($static_call_lines[$last_line_key], ',');
    $code = array_merge($code, $static_call_lines);

    $code[] = '  );';
    $code[] = '}';

    return $code;
  }

  /**
   * Creates the code lines for the __construct() method with DI.
   */
  protected function codeBodyClassMethodConstruct() {
    // Assemble the parameters to the __construct() method.
    // These are the base parameter + the parent injected services + our
    // injected services.
    $base_parameters = $this->getConstructBaseParameters();
    $parent_injected_services = $this->getConstructParentInjectedServices();

    $parameters = [];
    $parameters = array_merge($parameters, $base_parameters);
    $parameters = array_merge($parameters, $parent_injected_services);

    foreach ($this->childContentsGrouped['constructor_param'] as $service_parameter) {
      $parameters[] = $service_parameter;
    }

    // Build the docblock and declaration for the method.
    $code = $this->buildMethodHeader(
      '__construct',
      $parameters,
      [
        'docblock_first_line' => "Creates a {$this->component_data['plain_class_name']} instance.",
        'prefixes' => ['public'],
        // We want the __construct() method declaration's parameter to be
        // broken over multiple lines for legibility.
        // This is a Drupal coding standard still under discussion: see
        // https://www.drupal.org/node/1539712.
        'break_declaration' => TRUE,
      ]
    );

    if ($base_parameters || $parent_injected_services) {
      $parent_call_args = [];

      foreach ($base_parameters as $parameter) {
        $parent_call_args[] = '$' . $parameter['name'];
      }

      foreach ($parent_injected_services as $parameter) {
        $parent_call_args[] = '$' . $parameter['name'];
      }

      $code[] = '  ' . 'parent::__construct(' . implode(', ', $parent_call_args) . ');';
    }

    foreach ($this->childContentsGrouped['property_assignment'] as $content) {
      $code[] = "  \$this->{$content['property_name']} = \${$content['variable_name']};";
    }
    $code[] = '}';

    return $code;
  }

}
