<?php

namespace DrupalCodeBuilder\DependencyInjection;

use Composer\Script\Event;
use DrupalCodeBuilder\Attribute\InjectImplementations;
use Psr\Container\ContainerInterface;

/**
 * Service container builder.
 *
 * The following services are registered:
 * - The environment, as 'environment'.
 * - All classes in the \DrupalCodeBuilder\Task namespace except 'Generate',
 *   with as their service name the partial class name starting with the
 *   namespace below the 'Task' namespace, so for example, 'ReportSummary',
 *   'Generate\ComponentCollector'.
 * - The Generate task with a suffix for the root component type, in the form
 *   'Generate|component_type', for example, 'Generate|module'.
 *
 * Requesting a task class service will produce the versioned class based on the
 * environment's current Drupal major version number, so for example, if a task
 * class 'Foo7' exists, then requesting 'Foo' while running on Drupal 7 will
 * return a 'Foo7' object.
 *
 * This uses php-di/php-di rather than symfony/dependency_injection, because we
 * need the DCB package to run on different major versions of Drupal which will
 * use different versions of Symfony. The DI component is sufficiently different
 * in versions 3 and 4 of Symfony to make this impossible. Therefore the
 * simplest solution is to use a completely different DI package.
 *
 * To rebuild the container, do:
 *
 * $ composer cr
 *
 * This should only be used locally; the compiled container is committed to
 * the codebase repository.
 */
class ContainerBuilder {

  /**
   * Composer script callback to rebuild the cached container.
   */
  public static function rebuildCachedContainer(Event $event) {
    $cached_file = realpath(__DIR__ . '/cache/DrupalCodeBuilderCompiledContainer.php');
    if (file_exists($cached_file)) {
      $event->getIO()->write("Deleting cached container file.");
      unlink($cached_file);
    }

    // Include PHP-DI's functions, as Composer scripts don't autoload these.
    require_once("vendor/php-di/php-di/src/functions.php");

    // Include symfony var-dumper for development. This code is only ever run
    // during DCB development, so no need to comment it out.
    require_once('vendor/symfony/var-dumper/Resources/functions/dump.php');

    static::buildContainer();

    $event->getIO()->write("Written new cached container file.");
  }

  /**
   * Builds the container.
   */
  public static function buildContainer() {
    $builder = new \DI\ContainerBuilder();
    $class_loader = require('vendor/autoload.php');

    $builder->addDefinitions([
      // This is set to a dummy generic class because the container builder
      // needs one. This class is replaced when DCB is initialised by
      // \DrupalCodeBuilder\Factory::setEnvironment().
      'environment' => \DI\create(\DrupalCodeBuilder\Environment\DefaultEnvironment::class),
      // Alias the environment to the interface, so autowiring picks up the
      // environment parameter type.
      \DrupalCodeBuilder\Environment\EnvironmentInterface::class => \DI\get('environment'),
    ]);

    $services = [];
    $versioned_services = [];

    // Change directory to DCB's root directory. In an environment where DCB is
    // being developed with other packages (e.g. UIs that make use of it), it
    // will not be at the root, but in Composer's vendor folder.
    $previous_dir = getcwd();
    $base_task_path = $class_loader->findFile(\DrupalCodeBuilder\Factory::class);
    chdir(dirname($base_task_path));
    $task_files = glob('{Task,Task/*}/*.php', GLOB_BRACE);
    chdir($previous_dir);

    // Assembling the services for the container is done over multiple passes.
    // It would be nice if each of these could be its own class, as in Drupal
    // core. However, the ContainerBuilder doesn't allow retrieving services
    // and I don't know whether we can make changes to the Container before
    // building it.

    // Get files in the Task folder and its immediate subfolders.
    foreach ($task_files as $task_file) {
      $matches = [];
      preg_match('@Task/((?:\w+/)?\w+).php@', $task_file, $matches);
      $trimmed_file_name = $matches[1];

      // The service name is a partial class name, starting below the 'Task'
      // namespace, so for example, 'ReportSummary',
      // 'Generate\ComponentCollector'.
      $service_name = str_replace('/', '\\', $trimmed_file_name);

      $class_name = '\DrupalCodeBuilder\Task\\' . $service_name;

      // With versioned classes, keep track of the base unversioned name, as
      // these should not be registered in the bulk list.
      if (is_numeric(substr($service_name, -1))) {
        $unversioned_service_name = preg_replace('@\d+$@', '', $service_name);
        $versioned_services[$unversioned_service_name] = TRUE;
      }

      // Don't register abtract classes, interfaces, or traits.
      $reflector = new \ReflectionClass($class_name);
      if ($reflector->isAbstract()) {
        continue;
      }
      if ($reflector->isInterface()) {
        continue;
      }
      if ($reflector->isTrait()) {
        continue;
      }

      // Don't register 'Generate' yet -- it's complicated.
      if ($service_name == 'Generate') {
        continue;
      }

      $services[$service_name] = $class_name;
    } // foreach $task_files

    $definitions = [];

    // Define the services.
    foreach ($services as $service_name => $class_name) {
      if (!isset($versioned_services[$service_name])) {
        // Autowire anything that's not versioned.
        $definitions[$service_name] = \DI\autowire($class_name);
      }
    }

    // Define flavours of the Generate task.
    // Each of these needs to be its own service, as the Generate task gets
    // the root component as a construction parameter so different root
    // components need a different instance of the task.
    // WARNING! This requires the Composer class loader to be up to date and
    // generated with `composer dump --optimise`. Complain if this seems to be
    // the case.
    $class_map = $class_loader->getClassMap();
    if (!isset($class_map['DrupalCodeBuilder\Factory'])) {
      throw new \LogicException("Composer class map does not contain \DrupalCodeBuilder\Factory class; it likely needs to be rebuild with 'composer dump -o'.");
    }

    foreach ($class_loader->getClassMap() as $class_name => $class_filename) {
      if (!str_starts_with($class_name, 'DrupalCodeBuilder\Generator')) {
        continue;
      }

      require_once $class_filename;
      $reflection_class = new \ReflectionClass($class_name);

      if (!$reflection_class->getParentClass()) {
        continue;
      }

      // Note that this also eliminates versioned root components, such as
      // 'Module7' as they will inherit from the unversioned class.
      if ($reflection_class->getParentClass()->getShortName() != 'RootComponent') {
        continue;
      }

      $root_component_type = strtolower($reflection_class->getShortName());
      $service_name = 'Generate|' . $root_component_type;

      $definitions[$service_name] = \DI\factory([static::class, 'createGenerator'])
        ->parameter('root_component_type', $root_component_type);
    } // foreach class map

    // Define the versioned services. This needs a separate loop because some
    // of these classe are abstract, and so not in $services.
    foreach (array_keys($versioned_services) as $service_name) {
      // These can all use the same factory because the versioned class is also
      // a service, that gets autowired and the factory doesn't need to worry
      // about it.
      $definitions[$service_name] = \DI\factory([static::class, 'createVersioned']);

      if (isset($services[$service_name])) {
        // Create a normal autowired version of the unversioned service name, so
        // the factory can request it without circularity in the case that there
        // is no versioned service.
        $definitions[$service_name . '.unversioned'] = \DI\autowire($services[$service_name]);
      }
    }

    // AAARGH. This needs to be aliased, because autowiring finds this class
    // but tries to instantiate it because there's no declaration for it!
    // TODO: do this properly: for all abstract and unversioned classes,
    // register them as an alias for the short name.
    $definitions['DrupalCodeBuilder\Task\Collect\HooksCollector'] = \DI\get('Collect\HooksCollector');

    // Attribute-based method pass. Services may add an attribute
    // \DrupalCodeBuilder\Attribute\InjectImplementations to collector method,
    // specifying in the attribute an interface. All services which implement
    // this interface will be passed to that method by the container.
    $collection_interfaces = [];
    $collector_service_methods = [];
    foreach ($definitions as $service_name => $service_definition) {
      if (!isset($services[$service_name])) {
        continue;
      }

      $class_name = $services[$service_name];
      $class = new \ReflectionClass($class_name);

      foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->isStatic()) {
          continue;
        }

        $attribute = $method->getAttributes(InjectImplementations::class)[0] ?? NULL;

        if (!$attribute) {
          continue;
        }

        $inject = $attribute->newInstance();
        $interface = $inject->getInterface();

        // Assemble a list of collector services, so we only need to go once
        // through all services to look for interface implementors.
        $collection_interfaces[$service_name] = $interface;
        $collector_service_methods[$service_name] = $method->getName();
      }
    }
    // Now get all services that implement the collectable interfaces!
    $collections = [];
    foreach ($definitions as $service_name => $service_definition) {
      if (!isset($services[$service_name])) {
        continue;
      }

      $class_name = $services[$service_name];
      $class = new \ReflectionClass($class_name);
      // If the service implements any collection interfaces, add it to the
      // array of collections for that interface.
      foreach (array_intersect($class->getInterfaceNames(), $collection_interfaces) as $interface) {
        // Don't add versioned services: we only want the plain one to be used;
        // when it is obtained from the container, the self::createVersioned()
        // factory will take care of providing the right version.
        if (is_numeric(substr($service_name, -1))) {
          continue;
        }

        $collections[$interface][] = \DI\get($service_name);
      }
    }
    // Finally, define the method to call on the collector service definition.
    foreach ($collection_interfaces as $service_name => $interface) {
      if (empty($collections[$interface])) {
        continue;
      }

      // If the definition is a factory, then we actually want the unversioned
      // alias.
      // TODO: Why can't we use the __toString() method on the definitions to
      // check for this in a more API-ish way? API to get definition objects
      // from helper objects is really weird.
      if ($definitions[$service_name] instanceof \DI\Definition\Helper\FactoryDefinitionHelper) {
        $definition = $definitions[$service_name . '.unversioned'];
      }
      else {
        $definition = $definitions[$service_name];
      }

      // Have to pass in the services as an array, as method() only works with a
      // single parameter!!
      $definition->method(
        $collector_service_methods[$service_name],
        $collections[$interface]
      );
    }

    $builder->addDefinitions($definitions);

    // Wot no namespace for the compiled container? We're prefixing the class
    // name like PHP 5 savages??
    $builder->enableCompilation(__DIR__ . '/cache', 'DrupalCodeBuilderCompiledContainer');

    $container = $builder->build();

    return $container;
  }

  /**
   * Factory for versioned services which have no construction parameters.
   *
   * This is registered as the factory for the service name without a version
   * suffix, for example, 'Foo'. Instead of 'Foo', it will return:
   *  1. The service with the version suffix for the current major version, for
   *     example 'Foo10', if it exists.
   *  2. The service formed by adding the '.unversioned' suffix otherwise. This
   *     is an alias for the 'Foo' class, which is needed because trying to get
   *     'Foo' from the container will bring us right back to this factory
   *     method.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The container.
   * @param \DI\Factory\RequestedEntry $entry
   *   The requested service name.
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment.
   */
  public static function createVersioned(
    ContainerInterface $container,
    \DI\Factory\RequestedEntry $entry,
    \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
  ) {
    $requested_name = $entry->getName();
    $versioned_name = $requested_name . $environment->getCoreMajorVersion();

    if ($container->has($versioned_name)) {
      return $container->get($versioned_name);
    }
    else {
      // Get the plain version of the requested service, as otherwise we'd just
      // be requesting the service that brought us here.
      if (!$container->has($requested_name . '.unversioned')) {
        throw new \LogicException("There is no service '$versioned_name' or its unversioned fallback '$requested_name'.");
      }

      return $container->get($requested_name . '.unversioned');
    }
  }

  /**
   * Factory for flavours of the Generate task.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The container.
   * @param string $root_component_type
   *   The root component type.
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment.
   */
  public static function createGenerator(
    ContainerInterface $container,
    string $root_component_type,
    \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
  ) {
    return new \DrupalCodeBuilder\Task\Generate(
      $environment,
      $root_component_type,
      $container->get('Generate\ComponentClassHandler'),
      $container->get('Generate\ComponentCollector'),
      $container->get('Generate\FileAssembler'),
    );
  }

}
