<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use CaseConverter\CaseString;
use CaseConverter\StringAssembler;

/**
 * Task helper for collecting data on services.
 */
class ServicesCollector extends CollectorBase  {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'services';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'services';

  /**
   * The names of services to collect for testing sample data.
   */
  protected $testingServiceNames = [
    'current_user' => TRUE,
    'entity_type.manager' => TRUE,
    'module_handler' => TRUE,
    'cache.discovery' => TRUE,
    'storage:node' => TRUE,
  ];

  /**
   * The container builder helper.
   */
  protected $containerBuilderGetter;

  /**
   * Constructs a new helper.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment object.
   * @param ContainerBuilderGetter $method_collector
   *   The container builder helper.
   * @param \DrupalCodeBuilder\Task\Collect\CodeAnalyser $code_analyser
   *   The code analyser helper.
   */
  public function __construct(
    EnvironmentInterface $environment,
    ContainerBuilderGetter $container_builder_getter,
    CodeAnalyser $code_analyser
  ) {
    $this->environment = $environment;
    $this->containerBuilderGetter = $container_builder_getter;
    $this->codeAnalyser = $code_analyser;
  }

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    // No point splitting this up into jobs, getting the list of services is
    // pretty much the whole task.
    return NULL;
  }

  /**
   * Get definitions of services.
   *
   * @return array
   *   An array containing:
   *    - 'primary': The major services in use.
   *    - 'all': All services.
   *   Each value is itself an array of service data, keyed by service ID, where
   *   each value is an array containing:
   *    - 'id': The service ID.
   *    - 'label': A label for the service.
   *    - 'description': A longer description for the service.
   *    - 'class': The fully-qualified class that is defined for the service.
   *    - 'interface': The fully-qualified interface that the service class
   *      implements, with the initial '\'.
   *    - 'variable_name': The string to use as the name of a variable holding
   *      the service.
   *    - 'real_service': Set only for pseudoservices, which are not actual
   *      services but things that can be injected into class. In this case,
   *      this value is the ID of the actual service to get from the container.
   */
  public function collect($job_list = NULL) {
    $all_services = $this->getAllServices();
    $static_container_services = $this->getStaticContainerServices();

    // Filter out anything from the static inspection, to remove deprecated
    // services, and also in case some non-services snuck in.
    $static_container_services = array_intersect_key($static_container_services, $all_services);

    // Replace the definitions from the container with the hopefully better
    // data from the static Drupal class.
    foreach ($static_container_services as $service_id => $static_container_service) {
      $all_services[$service_id] = $static_container_service + $all_services[$service_id];
    }

    $pseudo_services = $this->getPseudoServices();
    $all_services = array_merge($all_services, $pseudo_services);

    // Filter for testing sample data collection.
    if (!empty($this->environment->sample_data_write)) {
      $static_container_services = array_intersect_key($static_container_services, $this->testingServiceNames);
      $all_services = array_intersect_key($all_services, $this->testingServiceNames);
    }

    // Sort by ID.
    ksort($all_services);
    ksort($static_container_services);

    $return = [
      'primary' => $static_container_services,
      'all' => $all_services,
    ];

    return $return;
  }

  /**
   * Gets the count of items in an array of data.
   *
   * @param array $data
   *   An array of analysis data.
   *
   * @return int
   */
  public function getDataCount($data) {
    // Services data consists of two lists of services.
    return count($data['all']);
  }

  /**
   * Get data on all services from the container builder.
   *
   * @return [type] [description]
   */
  protected function getAllServices(): array {
    $container_builder = $this->containerBuilderGetter->getContainerBuilder();
    $definitions = $container_builder->getDefinitions();

    // Get an array of all the tags which are used by service collectors, so
    // we can filter out the services with those tags.
    $collector_tags = [];
    $collectors_info = $container_builder->findTaggedServiceIds('service_collector');
    foreach ($collectors_info as $service_name => $tag_infos) {
      // A single service collector service can collect on more than one tag.
      foreach ($tag_infos as $tag_info) {
        $tag = $tag_info['tag'];
        $collector_tags[$tag] = TRUE;
      }
    }

    $data = [];

    foreach ($definitions as $service_id => $definition) {
      // Skip services from Drush.
      if (substr($service_id, 0, strlen('drush.')) == 'drush.') {
        continue;
      }
      if (substr($service_id, - strlen('.commands')) == '.commands') {
        continue;
      }

      // Skip any services which are tagged for a collector, as they should not
      // be directly used by anything else.
      if (array_intersect_key($collector_tags, $definition->getTags())) {
        continue;
      }

      // Skip any services which are marked as deprecated.
      if ($definition->isDeprecated()) {
        continue;
      }

      // Skip entity.manager, which is not marked as deprecated, but is.
      if ($service_id == 'entity.manager') {
        continue;
      }

      $service_class = $definition->getClass();

      // Skip if no class defined, class_loader apparently, WTF?!.
      if (empty($service_class)) {
        continue;
      }

      // Skip a proxied service: we use the renamed original instead; see below.
      // @see \Drupal\Core\DependencyInjection\Compiler\ProxyServicesPass
      if (preg_match('@ProxyClass@', $service_class)) {
        continue;
      }

      // Unproxy an original service that's been proxied.
      if (substr($service_id, 0, strlen('drupal.proxy_original_service.')) == 'drupal.proxy_original_service.') {
        $service_id = substr($service_id, strlen('drupal.proxy_original_service.'));
      }

      // Skip if the class isn't loadable by PHP without causing a fatal, as we
      // can't work with just an ID to generate things like injection.
      // (The case of a service class whose parent does not exist happens if
      // a module Foo provides a service for module Bar's collection with a
      // service tag. Metatag module is one such example.)
      if (!$this->codeAnalyser->classIsUsable($service_class)) {
        continue;
      }

      // Skip if the clas doesn't exist.
      // The service class can sometimes actually be an interface, as with
      // cache services. (This is not documented!)
      if (!(class_exists($service_class) || interface_exists($service_class))) {
        continue;
      }

      // Get the short class name to use as a label.
      $service_class_pieces = explode('\\', $service_class);
      $service_short_class = array_pop($service_class_pieces);

      // If the class is in fact secretly an interface (this is the case for
      // cache services!) then remove the 'Interface' suffix.
      $service_short_class = preg_replace('/Interface$/', '', $service_short_class);

      $label = CaseString::pascal($service_short_class)->sentence();

      // Append 'service' to the description, unless:
      //  - the label already contains 'service'
      //  - the label calls the service a 'manager'
      if (preg_match('@(service|manager)$@', $label)) {
        $description = "The {$label}";
      }
      else {
        $description = "The {$label} service";
      }

      // Prefix the class with a backslash if it doesn't already have it.
      if (substr($service_class, 0, 1) != '\\') {
        $service_class = '\\' . $service_class;
      }

      if (substr_count($service_id, '.') == 0) {
        // If the service name does not contain any dots, in particular,
        // 'current_user', then use that, as it's usually clearer than the
        // class name.
        $service_id_pieces = preg_split('@[_.]@', $service_id);
        $variable_name = (new StringAssembler($service_id_pieces))->snake();
      }
      else {
        // Otherwise, use the service class name.
        $class_pieces = explode('\\', $service_class);
        $short_class = array_pop($class_pieces);

        // Trim an 'Interface' suffix from the class in case it's actually an
        // interface. This is the case for cache services.
        $short_class = preg_replace('@Interface$@', '', $short_class);

        $variable_name = CaseString::pascal($short_class)->snake();
      }

      $data[$service_id] = [
        'id' => $service_id,
        'label' => $label,
        'static_method' => '', // Not used.
        'class' => $service_class,
        'interface' => $this->getServiceInterface($service_class),
        'description' => $description,
        'variable_name' => $variable_name,
      ];
    }

    return $data;
  }

  /**
   * Extracts the interface from the service class using reflection.
   *
   * This expects a service to only implement a single interface.
   *
   * @param string $service_class
   *   The fully-qualified class name of the service, with an initial '\'.
   *
   * @return string
   *   The fully-qualified name of the interface with the initial '\', or an
   *   empty string if no interface was found.
   */
  protected function getServiceInterface($service_class) {
    $reflection = new \ReflectionClass($service_class);
    // This can get us more than one interface, if the declared interface has
    // parents. We only want one, so we need to go hacking in the code itself.
    $file = $reflection->getFileName();

    // If there's no file, as apparently some services have as their class
    // ArrayObject (WTF?!).
    if (empty($file)) {
      return '';
    }

    $start_line = $reflection->getStartLine();
    $spl = new \SplFileObject($file);
    $spl->seek($start_line - 1);
    $service_declaration_line = $spl->current();

    // Extract the interface from the declaration. We expect a service class
    // to only name one interface in its declaration!
    $matches = [];
    preg_match("@implements ([\w\\\\]+)@", $service_declaration_line, $matches);

    if (!isset($matches[1])) {
      return '';
    }

    // If the interface wasn't imported, but is fully-qualified, we're done.
    // WARNING: this doesn't handle a class that has part of its namespace
    // imported!
    if (substr($matches[1], 0, 1) == '\\') {
      return $matches[1];
    }

    $interface_short_name = $matches[1];

    // Find the fully-qualified interface name in the array of interfaces
    // obtained from the reflection.
    $interfaces = $reflection->getInterfaces();
    foreach (array_keys($interfaces) as $interface) {
      if (substr($interface, -(strlen($interface_short_name) + 1)) == '\\' . $interface_short_name) {
        // The reflection doesn't give the initial '\'.
        $full_interface_name = '\\' . $interface;

        return $full_interface_name;
      }
    }

    return '';
  }

  /**
   * Get data on services from the static container.
   *
   * This examines the \Drupal static helper using reflection, and extracts
   * data from the helper methods that return services.
   *
   * @return array
   *   An array of service data.
   */
  protected function getStaticContainerServices(): array {
    $container_builder = $this->containerBuilderGetter->getContainerBuilder();

    // We can get service IDs from the container,
    $static_container_reflection = new \ReflectionClass('\Drupal');
    $filename = $static_container_reflection->getFileName();
    $source = file($filename);

    $methods = $static_container_reflection->getMethods();
    $service_definitions = [];
    foreach ($methods as $method) {
      $name = $method->getName();

      // Skip any which have parameters: the service getter methods have no
      // parameters.
      if ($method->getNumberOfParameters() > 0) {
        continue;
      }

      $start_line = $method->getStartLine();
      $end_line = $method->getEndLine();

      // Skip any which have more than 2 lines: the service getter methods have
      // only 1 line of code.
      if ($end_line - $start_line > 2) {
        continue;
      }

      // Get the single code line.
      $code_line = $source[$start_line];

      // Extract the service ID from the call to getContainer().
      $matches = [];
      $code_line_regex = "@return static::getContainer\(\)->get\('([\w.]+)'\);@";
      if (!preg_match($code_line_regex, $code_line, $matches)) {
        continue;
      }
      $service_id = $matches[1];

      $docblock = $method->getDocComment();

      // Extract the interface for the service from the docblock @return.
      $matches = [];
      preg_match("[@return (.+)]", $docblock, $matches);
      $interface = $matches[1];

      // Extract a description from the docblock first line.
      $docblock_lines = explode("\n", $docblock);
      $doc_first_line = $docblock_lines[1];

      $matches = [];
      preg_match("@(the (.*))\.@", $doc_first_line, $matches);
      $description = ucfirst($matches[1]);
      $label = ucfirst($matches[2]);

      $definition = $container_builder->getDefinition($service_id);

      $service_definition = [
        'id' => $service_id,
        'label' => $label,
        'static_method' => $name, // not used.
        'class' => '\\' . $definition->getClass(),
        'interface' => $interface,
        'description' => $description,
      ];
      $service_definitions[$service_id] = $service_definition;
    }

    return $service_definitions;
  }

  /**
   * Define pseudo-services.
   *
   * These are not actual services in the container, but objects that are
   * commonly injected into classes.
   *
   * Their IDs in the data are of the form TYPE:VARIANT, where TYPE identifies
   * the type of pseudoservice, such as 'storage' and variant gives the
   * specific object, such as 'node'.
   */
  protected function getPseudoServices() {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $entity_types = $entity_type_manager->getDefinitions();

    $entity_storage_pseudo_services = [];
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $pseudo_service_id = "storage:{$entity_type_id}";
      $entity_storage_pseudo_services[$pseudo_service_id] = [
        'id' => $pseudo_service_id,
        'label' => $entity_type->getLabel() . ' storage',
        'static_method' => '', // Not used.
        // 'class' => '\\' . $service_class,
        'interface' => '\Drupal\Core\Entity\EntityStorageInterface',
        // TODO; no don't use labels in code.
        'description' => "The {$entity_type_id} storage handler",
        'variable_name' => "{$entity_type_id}_storage",
        'real_service' => 'entity_type.manager',
        'service_method' => 'getStorage',
      ];
    }

    return $entity_storage_pseudo_services;
  }

}
