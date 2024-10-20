<?php

namespace DrupalCodeBuilder\DependencyInjection;

use Composer\Script\Event;
use DI\ContainerBuilder as DIContainerBuilder;
use DrupalCodeBuilder\Attribute\InjectImplementations;
use DrupalCodeBuilder\Attribute\DrupalCoreVersion;
use DrupalCodeBuilder\Attribute\RelatedBaseClass;

/**
 * Service container builder.
 *
 * This class is for use during development. The compiled container is committed
 * to the codebase repository.
 *
 * The container should be rebuilt whenever Generator or Task classes are added
 * or have their version attributes changed. To rebuild the container, do:
 *
 * $ composer dcb-cr
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
 * The terminology around versioned classes in the context of the container is
 * as follows:
 *  - 'versioned class': A class with a version number suffix, e.g. 'Foo9'. In
 *    the documentation, 'FooN' is used with 'N' meaning any version number.
 *  - 'multi-versioned class': A class with multiple
 *    \DrupalCodeBuilder\Attribute\DrupalCoreVersion attributes, which declare
 *    it as being for several specific versions.
 *  - 'singleton class': A class which has no version number, for which no
 *    versioned class exists. E.g., 'Foo', and there is no 'FooN'.
 *  - 'base class': The class with no version number that corresponds to a
 *    versioned class. For example, given 'Foo9', then 'Foo' is the base class.
 *    This is not necessarily a PHP parent class; in the service container
 *    domain we are not concerned with class inheritance.
 *  - 'unversioned class': Ambiguous term, as could mean either 'singleton
 *    class' or 'base class'. Avoid.
 *
 * With classes that may be versioned, such as Task classes, the following
 * services are registered in the container:
 * - For a singleton class, the class itself is a service, registered as
 *   autowired.
 * - For a versioned class, the class itself is a service, registered as
 *   autowired.
 * - A base class is registered as a service, even if the base class does not
 *   actually exist! That is, if there is a 'FooN' class for every supported
 *   Drupal cover version and no 'Foo' class, then 'Foo' is still registered as
 *   a service because that is what the calling code will ask the container for.
 *   Whether the class exists or not, the service is registered with the factory
 *   method ServiceFactories::createVersioned(), which handles finding the
 *   service for the versioned class to return instead.
 * - A base class (that actually exists) is additionally registered as a service
 *   whose name has the suffix '.unversioned', registered with autowiring. This
 *   allows that actual class to be obtained from the container, because the
 *   name without that suffix leads to the ServiceFactories::createVersioned()
 *   factory. This is for the case where there is no versioned class for the
 *   current core version. E.g., class 'Foo' is requested on version 9, but
 *   there is no class 'Foo9' and so the actual class 'Foo' should be returned
 */
class ContainerBuilder {

  /**
   * The path to this package.
   *
   * @var string
   */
  protected static $drupal_code_builder_path;

  /**
   * Definitions to set on the container once complete.
   *
   * @var array
   */
  protected static $definitions = [];

  /**
   * An array of service name => full class name.
   *
   * @var array
   */
  protected static $services = [];

  /**
   * An array of potential service name => full class name, with all classes.
   *
   * Contains classes that are filtered out and not defined as services.
   *
   * @var array
   */
  protected static $all_classes = [];

  /**
   * The service names of services that have versioned variants.
   *
   * For example, would contain Foo if there is a Foo8.
   *
   * @var array
   */
  protected static $base_class_service_names = [];

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
    $builder = new DIContainerBuilder();

    // Get the path to this package so we can look for files in it. This also
    // works if this package is the root.
    static::$drupal_code_builder_path = \Composer\InstalledVersions::getInstallPath('drupal-code-builder/drupal-code-builder');
    static::$drupal_code_builder_path = realpath(static::$drupal_code_builder_path);

    // Assembling the services for the container is done over multiple passes.
    // It would be nice if each of these could be its own class, as in Drupal
    // core. However, the ContainerBuilder doesn't allow retrieving services
    // and I don't know whether we can make changes to the Container before
    // building it. Therefore we build up an array of $definitions, and pass
    // that to the container builder once we're done.
    static::environmentPass();
    static::generatorClassmapPass();
    static::basicTasksPass();
    static::generateTaskPass();
    static::baseClassPass();
    static::specialCasesPass();
    static::attributeMethodInjectionPass();

    $builder->useAttributes(TRUE);
    $builder->addDefinitions(static::$definitions);

    // Wot no namespace for the compiled container? We're prefixing the class
    // name like PHP 5 savages??
    $builder->enableCompilation(__DIR__ . '/cache', 'DrupalCodeBuilderCompiledContainer');

    try {
      $container = $builder->build();
    }
    catch (\Exception $e) {
      // Composer doesn't show a backtrace for an exception WTF.
      dump($e->getTrace());

      throw $e;
    }

    return $container;
  }

  /**
   * Defines the environment service.
   */
  protected static function environmentPass() {
    // This is set to a dummy generic class because the container builder
    // needs one. This class is replaced when DCB is initialised by
    // \DrupalCodeBuilder\Factory::setEnvironment().
    static::$definitions['environment'] = \DI\create(\DrupalCodeBuilder\Environment\DefaultEnvironment::class);

    // Alias the environment to the interface, so autowiring picks up the
    // environment parameter type.
    static::$definitions[\DrupalCodeBuilder\Environment\EnvironmentInterface::class] = \DI\get('environment');
  }

  /**
   * Defines the generator classmap.
   *
   * This is a lookup for versioned and multiversioned generator classes, used
   * by ComponentClassHandler.
   *
   * Generators aren't registered as services, because:
   *  - As well as being instantiated objects, the classes are used statically,
   *    and the container doesn't support getting the class for a service, and
   *    having two separate systems for getting the generator, one static and
   *    one instantiated, seems fiddly.
   *  - A bug in PHP-DI doesn't allow using make() to instantiate an object with
   *    a compiled container, and the workaround of making the constructor
   *    parameters optional is ugly. (see
   *    https://github.com/PHP-DI/PHP-DI/issues/893.)
   */
  protected static function generatorClassmapPass() {
    $previous_dir = getcwd();
    chdir(static::$drupal_code_builder_path);
    $generator_files = glob('Generator/*.php');
    chdir($previous_dir);

    // Build up a mapping of component types to versioned class names.
    $component_type_mapping = [];

    foreach ($generator_files as $generator_file) {
      $short_class_name = basename($generator_file, '.php');
      $full_class_name = '\DrupalCodeBuilder\Generator\\' . $short_class_name;

      $reflector = new \ReflectionClass($full_class_name);

      // Multi-versioned classes use attributes to declare the versions they are
      // for and the plain class.
      if ($core_version_attributes = $reflector->getAttributes(DrupalCoreVersion::class)) {
        $base_class_attributes = $reflector->getAttributes(RelatedBaseClass::class);
        if (empty($base_class_attributes)) {
          throw new \LogicException("Class $full_class_name has DrupalCoreVersion attributes but no RelatedBaseClass attribute.");
        }
        $base_class = $base_class_attributes[0]->newInstance()->base_class;

        // Register a mapping item for each version the class supports.
        foreach ($core_version_attributes as $attribute) {
          $component_type_mapping[$base_class][$attribute->newInstance()->core_version] = $short_class_name;
        }

        // Done with this class.
        continue;
      }

      // Versioned classes use a numeric suffix on the plain class name.
      if (is_numeric(substr($short_class_name, -1))) {
        // A class is versioned if its name has a numeric suffix, e.g. 'Foo10'.
        [$base_class, $version_number] = preg_split('/(\d+$)/', $short_class_name, flags: PREG_SPLIT_DELIM_CAPTURE);

        $component_type_mapping[$base_class][$version_number] = $short_class_name;
      }
    }

    // Add the classmap to service definitions as a constant value.
    static::$definitions['generator_classmap'] = $component_type_mapping;
  }

  /**
   * Defines the task classes as services.
   */
  protected static function basicTasksPass() {
    // Change directory to DCB's root directory. In an environment where DCB is
    // being developed with other packages (e.g. UIs that make use of it), it
    // will not be at the root, but in Composer's vendor folder.
    $previous_dir = getcwd();
    chdir(static::$drupal_code_builder_path);
    $task_files = glob('{Task,Task/*}/*.php', GLOB_BRACE);
    chdir($previous_dir);

    // Get files in the Task folder and its immediate subfolders.
    $services = [];
    foreach ($task_files as $task_file) {
      $matches = [];
      preg_match('@Task/((?:\w+/)?\w+).php@', $task_file, $matches);
      $trimmed_file_name = $matches[1];

      // The service name is a partial class name, starting below the 'Task'
      // namespace, so for example, 'ReportSummary',
      // 'Generate\ComponentCollector'.
      $service_name = str_replace('/', '\\', $trimmed_file_name);

      $class_name = '\DrupalCodeBuilder\Task\\' . $service_name;

      static::$all_classes[$service_name] = $class_name;

      // With versioned classes, keep track of the base unversioned name, as
      // that should not be registered in this pass, but handled separately in
      // static::baseClassPass(). This includes abstract classes, and
      // even potentially class names that don't exist.
      if (is_numeric(substr($service_name, -1))) {
        $unversioned_service_name = preg_replace('@\d+$@', '', $service_name);
        static::$base_class_service_names[$unversioned_service_name] = TRUE;
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
      static::$services[$service_name] = $class_name;
    } // foreach $task_files

    // Define the services.
    foreach ($services as $service_name => $class_name) {
      if (!isset(static::$base_class_service_names[$service_name])) {
        // Add all services, with autowiring, except for unversioned variants.
        // That is, if 'Foo9' exists, then 'Foo' is not added here.
        static::$definitions[$service_name] = \DI\autowire($class_name);
      }
    }
  }

  /**
   * Define flavours of the Generate task.
   *
   * Each of these needs to be its own service, as the Generate task gets
   * the root component as a construction parameter so different root
   * components need a different instance of the task.
   */
  protected static function generateTaskPass() {
    $previous_dir = getcwd();
    chdir(static::$drupal_code_builder_path);
    $generator_files = glob('Generator/*.php', GLOB_BRACE);
    chdir($previous_dir);

    foreach ($generator_files as $generator_file) {
      $matches = [];
      preg_match('@Generator/(\w+).php@', $generator_file, $matches);
      $trimmed_file_name = $matches[1];
      $class_name = '\DrupalCodeBuilder\Generator\\' . $trimmed_file_name;

      // Allow for junk files left during development...
      if (!class_exists($class_name)) {
        continue;
      }

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

      static::$definitions[$service_name] = \DI\factory([ServiceFactories::class, 'createGenerator'])
        ->parameter('root_component_type', $root_component_type);
    } // foreach class map
  }

  /**
   * Define unversioned services.
   *
   * Services that exist in different versions, such as Foo9, Foo10, also have
   * a plain version, Foo, which should be used when there is no versioned class
   * for the current Drupal core version.
   *
   * The plain version Foo uses a factory which returns the correct
   * version of the service.
   *
   * However, the factory sometimes needs to return the actual Foo class, in the
   * case where the versioned number doesn't exist. For this case, we define a
   * Foo.unversioned service which returns the plain class, since the Foo
   * service name is already taken.
   *
   * To summarize:
   *  - Foo: might return Foo9 or Foo.unversioned
   *  - Foo9: version of Foo for Drupal 9
   *  - Foo.unversioned: version of Foo for all other Drupal core versions.
   *
   * @see DrupalCodeBuilder\DependencyInjection\ServiceFactories::createVersioned()
   */
  protected static function baseClassPass() {
    // This needs a separate loop because some of these classes are abstract,
    // and so not in $services.
    foreach (array_keys(static::$base_class_service_names) as $service_name) {
      // These can all use the same factory because the versioned class is also
      // a service, that gets autowired and the factory doesn't need to worry
      // about it.
      static::$definitions[$service_name] = \DI\factory([ServiceFactories::class, 'createVersioned']);

      if (isset(static::$services[$service_name])) {
        // Create a normal autowired version of the unversioned service name, so
        // the factory can request it without circularity in the case that there
        // is no versioned service.
        static::$definitions[$service_name . '.unversioned'] = \DI\autowire(static::$services[$service_name]);
      }
    }
  }

  /**
   * Weird special cases pass.
   */
  protected static function specialCasesPass() {
    // AAARGH. This needs to be aliased, because autowiring finds this class
    // but tries to instantiate it because there's no declaration for it!
    // TODO: do this properly: for all abstract and unversioned classes,
    // register them as an alias for the short name.
    static::$definitions['DrupalCodeBuilder\Task\Collect\HooksCollector'] = \DI\get('Collect\HooksCollector');
  }

  /**
   * Attribute-based method injection pass.
   *
   * Services may add an attribute
   * \DrupalCodeBuilder\Attribute\InjectImplementations to a collector method,
   * specifying in the attribute an interface. All services which implement this
   * interface will be passed to that method by the container.
   */
  protected static function attributeMethodInjectionPass() {
    $collection_interfaces = [];
    $collector_service_methods = [];
    foreach (static::$definitions as $service_name => $service_definition) {
      if (!isset(static::$services[$service_name])) {
        continue;
      }

      $class_name = static::$services[$service_name];
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
    foreach (static::$definitions as $service_name => $service_definition) {
      // Skip anything that isn't a class, such as the flavours of Generate.
      if (!isset(static::$all_classes[$service_name])) {
        continue;
      }

      // We check all classes, not just services, as if the unversioned service
      // is an abstract class (because versions exist for all core versions),
      // it won't be a service.
      $class_name = static::$all_classes[$service_name];
      $class = new \ReflectionClass($class_name);
      // If the service implements any collection interfaces, add it to the
      // array of collections for that interface.
      foreach (array_intersect($class->getInterfaceNames(), $collection_interfaces) as $interface) {
        // Don't add versioned services: we only want the plain one to be used;
        // when it is obtained from the container, the
        // ServiceFactories::createVersioned() factory will take care of
        // providing the right version.
        if (is_numeric(substr($service_name, -1))) {
          continue;
        }

        $collections[$interface][$service_name] = \DI\get($service_name);
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
      if (static::$definitions[$service_name] instanceof \DI\Definition\Helper\FactoryDefinitionHelper) {
        $definition = static::$definitions[$service_name . '.unversioned'];
      }
      else {
        $definition = static::$definitions[$service_name];
      }

      // Have to pass in the services as an array, as method() only works with a
      // single parameter!!
      // See https://github.com/PHP-DI/PHP-DI/issues/881.
      $definition->method(
        $collector_service_methods[$service_name],
        $collections[$interface]
      );
    }
  }

}
