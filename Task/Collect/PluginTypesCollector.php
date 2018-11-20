<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Task helper for collecting data on plugin types.
 */
class PluginTypesCollector extends CollectorBase  {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'plugins';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'plugin types';

  /**
   * The container builder helper.
   */
  protected $containerBuilderGetter;

  /**
   * The method collector helper
   */
  protected $methodCollector;

  /**
   * The names of plugin type managers to collect for testing sample data.
   */
  protected $testingPluginManagerServiceIds = [
    // Annotation plugins.
    'plugin.manager.block',
    'plugin.manager.field.formatter',
    'plugin.manager.image.effect',
    // Annotation plugin using only the plugin ID in its annotation.
    'plugin.manager.element_info',
    // YAML plugins. Need all of these for entity type generators.
    'plugin.manager.menu.link',
    'plugin.manager.menu.local_task',
    'plugin.manager.menu.local_action',
  ];

  /**
   * Constructs a new helper.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment object.
   * @param ContainerBuilderGetter $method_collector
   *   The container builder helper.
   * @param MethodCollector $method_collector
   *   The method collector helper.
   * @param \DrupalCodeBuilder\Task\Collect\CodeAnalyser $code_analyser
   *   The code analyser helper.
   */
  public function __construct(
    EnvironmentInterface $environment,
    ContainerBuilderGetter $container_builder_getter,
    MethodCollector $method_collector,
    CodeAnalyser $code_analyser
  ) {
    $this->environment = $environment;
    $this->containerBuilderGetter = $container_builder_getter;
    $this->methodCollector = $method_collector;
    $this->codeAnalyser = $code_analyser;
  }

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    $plugin_manager_service_ids = $this->getPluginManagerServices();

    // Filter for testing sample data collection.
    if (!empty($this->environment->sample_data_write)) {
      // Note this is not an intersect on keys like the other collectors!
      $plugin_manager_service_ids = array_intersect($plugin_manager_service_ids, $this->testingPluginManagerServiceIds);
    }
    //$plugin_manager_service_ids = ['plugin.manager.block'];

    // Convert to a numeric array.
    $job_list = [];
    foreach ($plugin_manager_service_ids as $plugin_manager_service_id) {
      // Derive a plugin type ID.
      if (strpos($plugin_manager_service_id, 'plugin.manager.') === 0) {
        // We identify plugin types where the service name follows the standard
        // pattern by the part of the plugin manager service name that comes
        // after 'plugin.manager.'.
        $plugin_type_id = substr($plugin_manager_service_id, strlen('plugin.manager.'));
      }
      else {
        // Non-standard service names often end in '.manager' or '_manager'.
        $plugin_type_id = preg_replace('@.manager$@', '', $plugin_manager_service_id);
      }

      $job_list[] = [
        'service_id' => $plugin_manager_service_id,
        'type_id' => $plugin_type_id,
        'process_label' => 'plugin type',
        'item_label' => $plugin_manager_service_id,
      ];
    }

    // Sort by the plugin type ID.
    usort($job_list, function($a, $b) {
      return strcmp($a['type_id'], $b['type_id']);
    });

    return $job_list;
  }

  /**
   * Get definitions of plugin types.
   *
   * @return array
   *   An array of data about plugin types. See gatherPluginTypeInfo() for the
   *   details.
   */
  public function collect($job_list) {
    $plugin_type_data = $this->gatherPluginTypeInfo($job_list);

    return $plugin_type_data;
  }

  /**
   * Detects services which are plugin managers.
   *
   * @return
   *  An array of service IDs of all the services which we detected to be plugin
   *  managers.
   */
  protected function getPluginManagerServices() {
    $container_builder = $this->containerBuilderGetter->getContainerBuilder();

    // Get the definitions of services from the container.
    $definitions = $container_builder->getDefinitions();

    // Filter them down to the ones that are plugin managers.
    $plugin_manager_service_ids = [];
    foreach ($definitions as $service_id => $definition) {
      // Skip any deprecated service.
      if ($definition->isDeprecated()) {
        continue;
      }

      // Assume that any service whose name is of the form plugin.manager.FOO
      // is a plugin type manager.
      if (strpos($service_id, 'plugin.manager.') === 0) {
        $plugin_manager_service_ids[] = $service_id;

        continue;
      }

      // Skip the entity type manager: these don't behave at all like normal
      // plugins.
      if ($service_id == 'entity_type.manager') {
        continue;
      }

      // Find any plugin managers which don't conform to the pattern for the
      // service name, but do inherit from the DefaultPluginManager class.
      $service_class = $definition->getClass();
      if (is_subclass_of($service_class, \Drupal\Core\Plugin\DefaultPluginManager::class)) {
        $plugin_manager_service_ids[] = $service_id;
      }
    }

    // Filter out a blacklist of known broken plugin types.
    $plugin_manager_service_ids = array_diff($plugin_manager_service_ids, [
      // https://www.drupal.org/project/ctools/issues/2938586
      'plugin.manager.ctools.relationship',
    ]);

    //drush_print_r($plugin_manager_service_ids);

    // Developer trapdoor: just process the block plugin type, to make terminal
    // debug output easier to read through.
    //$plugin_manager_service_ids = array('plugin.manager.block');

    return $plugin_manager_service_ids;
  }

  /**
   * Detects information about plugin types from the plugin manager services.
   *
   * @param $job_list
   *  A numeric array whose values are arrays containing:
   *  - 'service_id': The service ID of the plugin type manager.
   *  - 'type_id': The derived ID of the plugin type.
   *
   * @return
   *  The assembled plugin type data. This is an array keyed by plugin type ID.
   *  Values are arrays with the following properties:
   *    - 'type_id': The plugin type ID. We take this to be the name of the
   *      plugin manager service for that type, with the 'plugin.manager.'
   *      prefix removed.
   *    - 'type_label': A label for the plugin type. If Plugin module is present
   *      then this is the label from the definition there, if found. Otherwise,
   *      this duplicates the plugin type ID.
   *    - 'service_id': The ID of the service for the plugin type's manager.
   *    - 'discovery': The plugin's discovery class, as a fully-qualified
   *       class name (without the initial '\').
   *    - 'alter_hook_name': The short name of the alter hook for this plugin
   *      type.
   *    - 'subdir': The subdirectory of /src that plugin classes must go in.
   *      E.g., 'Plugin/Filter'.
   *    - 'plugin_interface': The interface that plugin classes must implement,
   *      as a qualified name (but without initial '\').
   *    - 'plugin_definition_annotation_name': The class that the plugin
   *      annotation uses, as a qualified name (but without initial '\').
   *      E.g, 'Drupal\filter\Annotation\Filter'.
   *    - 'plugin_interface_methods': An array of methods that the plugin's
   *      interface has. This is keyed by the method name, with each value an
   *      array with these properties:
   *      - 'name': The method name.
   *      - 'declaration': The method declaration line from the interface.
   *      - 'description': The text from the first line of the docblock.
   *    - 'plugin_properties: Properties that the plugin class may declare in
   *      its annotation. These are deduced from the class properties of the
   *      plugin type's annotation class. An array keyed by the property name,
   *      whose values are arrays with these properties:
   *      - 'name': The name of the property.
   *      - 'description': The description, taken from the docblock of the class
   *        property on the annotation class.
   *      - 'type': The data type.
   *    - 'annotation_id_only': Boolean indicating whether the annotation
   *      consists of only the plugin ID.
   *    - 'construction': An array of injected service parameters for the plugin
   *      base class. Each item has:
   *      - 'type': The typehint.
   *      - 'name': The parameter name, without the $.
   *      - 'extraction': The code for getting the service from the container.
   *    - 'constructor_fixed_parameters': An array of the fixed parameters for
   *      plugin base class __construct(). This is only set if they differ from
   *      the standard ones.
   *      - 'type': The typehint.
   *      - 'name': The parameter name, without the $.
   *      - 'extraction': The code for getting the value to pass in from the
   *        parameters passed to create().
   *    - 'config_schema_prefix': The prefix to use for creating a config schema
   *      ID for plugins of this type. The ID should be formed by appending the
   *      plugin ID with a '.'.
   *    - 'yaml_file_suffix': The suffix for YAML plugin files.
   *    - 'yaml_properties': The default properties for plugins declared in YAML
   *      files. An array whose keys are property names and values are the
   *      default values.
   *    - 'broken_plugins': (internal) An array of any plugins which could not
   *      be correctly loaded. Keys are plugin IDs; values are a brief reason.
   *
   *  Due to the difficult nature of analysing the code for plugin types, some
   *  of these properties may be empty if they could not be deduced.
   */
  protected function gatherPluginTypeInfo($job_list) {
    // Assemble a basic array of plugin type data, that we will successively add
    // data to.
    $plugin_type_data = array();
    foreach ($job_list as $job) {
      $plugin_manager_service_id = $job['service_id'];
      $plugin_type_id = $job['type_id'];

      // Get the class name for the service.
      // Babysit modules that don't define services properly!
      // (I'm looking at Physical.)
      try {
        $service = \Drupal::service($plugin_manager_service_id);
      }
      catch (\Throwable $ex) {
        continue;
      }

      $service = \Drupal::service($plugin_manager_service_id);
      $service_class_name = get_class($service);
      $service_component_namespace = $this->getClassComponentNamespace($service_class_name);

      $plugin_type_data[$plugin_type_id] = [
        'type_id' => $plugin_type_id,
        'service_id' => $plugin_manager_service_id,
        'service_class_name' => $service_class_name,
        'service_component_namespace' => $service_component_namespace,
        // Plugin module may replace this if present.
        'type_label' => $plugin_type_id,
      ];

      // Add data from the plugin type manager service.
      // This gets us the subdirectory, interface, and annotation name.
      $this->addPluginTypeServiceData($plugin_type_data[$plugin_type_id]);

      // Try to detect a base class for plugins
      $this->addPluginBaseClass($plugin_type_data[$plugin_type_id]);

      // Try to detect a config schema prefix.
      $this->addConfigSchemaPrefix($plugin_type_data[$plugin_type_id]);

      // Add data about the factory method of the base class, if any.
      $this->addConstructionData($plugin_type_data[$plugin_type_id]);

      // Do the large arrays last, so they are last in the stored data so it's
      // easier to read.
      // Add data from the plugin annotation class.
      $this->addPluginAnnotationData($plugin_type_data[$plugin_type_id]);

      // Add data from the plugin interface (which the manager service gave us).
      $this->addPluginMethods($plugin_type_data[$plugin_type_id]);
    }

    // Get plugin type information if Plugin module is present.
    // We get data on all plugin types in one go, so this handles the whole
    // data.
    $this->addPluginModuleData($plugin_type_data);

    //drush_print_r($plugin_type_data);

    return $plugin_type_data;
  }

  /**
   * Adds plugin type information from the plugin type manager service.
   *
   * This adds:
   *  - subdir
   *  - alter_hook_name
   *  - ... further properties specific to different discovery types.
   *
   * @param &$data
   *  The data for a single plugin type.
   */
  protected function addPluginTypeServiceData(&$data) {
    // Get a reflection for the plugin manager service.
    $service = \Drupal::service($data['service_id']);
    $reflection = new \ReflectionClass($service);

    // Determine the alter hook name.
    if ($reflection->hasProperty('alterHook')) {
      $property_alter_hook = $reflection->getProperty('alterHook');
      $property_alter_hook->setAccessible(TRUE);
      $alter_hook_name = $property_alter_hook->getValue($service);
      if (!empty($alter_hook_name)) {
        $data['alter_hook_name'] = $alter_hook_name . '_alter';
      }
    }

    // Determine the plugin discovery type.
    // Get the discovery object from the plugin manager.
    $method_getDiscovery = $reflection->getMethod('getDiscovery');
    $method_getDiscovery->setAccessible(TRUE);
    $discovery = $method_getDiscovery->invoke($service);
    $class_dicovery = new \ReflectionClass($discovery);

    // This is typically decorated at least once, for derivative discover.
    // Ascend up the chain of decorated objects to the innermost discovery
    // object.
    while ($class_dicovery->hasProperty('decorated')) {
      $property_decorated = $class_dicovery->getProperty('decorated');
      $property_decorated->setAccessible(TRUE);
      $discovery = $property_decorated->getValue($discovery);
      $class_dicovery = new \ReflectionClass($discovery);
    }

    $inner_discovery_class = get_class($discovery);

    // See if the plugin type's discovery class inherits from one of the ones
    // we handle.
    $known_discovery_classes = [
      'Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery',
      'Drupal\Core\Plugin\Discovery\YamlDiscovery',
      // We don't handle this type; only migrations use it. But we don't want
      // to ascend the class hierarchy for this type as then we'll get
      // YamlDiscovery and the analysis for that will fail.
      'Drupal\Core\Plugin\Discovery\YamlDirectoryDiscovery',
    ];

    if (in_array($inner_discovery_class, $known_discovery_classes)) {
      $data['discovery'] = $inner_discovery_class;
    }
    else {
      $discovery_class = $inner_discovery_class;
      while ($discovery_class = get_parent_class($discovery_class)) {
        if (in_array($discovery_class, $known_discovery_classes)) {
          $data['discovery'] = $discovery_class;
          break;
        }
      }

      // We didn't find a class we know in the parents: just set the inner
      // discovery class.
      if (!isset($data['discovery'])) {
        $data['discovery'] = $inner_discovery_class;
      }
    }

    // Get more data from the service, depending on the discovery type of the
    // plugin.
    $discovery_pieces = explode('\\', $data['discovery']);
    $discovery_short_name = array_pop($discovery_pieces);

    // Add in empty properties from the different discovery types analyses so
    // they are always present.
    $discovery_dependent_properties = [
      'subdir',
      'plugin_interface',
      'plugin_definition_annotation_name',
      'yaml_file_suffix',
      'yaml_properties',
      'annotation_id_only',
    ];
    foreach ($discovery_dependent_properties as $property) {
      $data[$property] = NULL;
    }

    switch ($discovery_short_name) {
      case 'AnnotatedClassDiscovery':
        $this->addPluginTypeServiceDataAnnotated($data, $service, $discovery);
        break;
      case 'YamlDiscovery':
        $this->addPluginTypeServiceDataYaml($data, $service, $discovery);
        break;
    }
  }

  /**
   * Analyse the plugin type manager service for annotated plugins.
   *
   * This adds:
   *  - subdir
   *  - plugin_interface
   *  - plugin_definition_annotation_name
   *
   * @param &$data
   *  The data for a single plugin type.
   * @param $service
   *  The plugin manager service.
   * @param $discovery
   *  The plugin discovery object.
   */
  protected function addPluginTypeServiceDataAnnotated(&$data, $service, $discovery) {
    $reflection = new \ReflectionClass($service);

    // Get the properties that the plugin manager constructor sets.
    // E.g., most plugin managers pass this to the parent:
    //   parent::__construct('Plugin/Block', $namespaces, $module_handler, 'Drupal\Core\Block\BlockPluginInterface', 'Drupal\Core\Block\Annotation\Block');
    // See Drupal\Core\Plugin\DefaultPluginManager
    // The list of properties we want to grab out of the plugin manager
    //  => the key in the plugin type data array we want to set this into.
    $plugin_manager_properties = [
      'subdir' => 'subdir',
      'pluginInterface' => 'plugin_interface',
      'pluginDefinitionAnnotationName' => 'plugin_definition_annotation_name',
    ];
    foreach ($plugin_manager_properties as $property_name => $data_key) {
      if (!$reflection->hasProperty($property_name)) {
        // plugin.manager.menu.link is different.
        $data[$data_key] = '';
        continue;
      }

      $property = $reflection->getProperty($property_name);
      $property->setAccessible(TRUE);
      $data[$data_key] = $property->getValue($service);
    }
  }

  /**
   * Analyse the plugin type manager service for YAML plugins.
   *
   * This adds:
   *  - yaml_file_suffix
   *  - yaml_properties
   *
   * @param &$data
   *  The data for a single plugin type.
   * @param $service
   *  The plugin manager service.
   * @param $discovery
   *  The plugin discovery object.
   */
  protected function addPluginTypeServiceDataYaml(&$data, $service, $discovery) {
    $service_reflection = new \ReflectionClass($service);
    $property = $service_reflection->getProperty('defaults');
    $property->setAccessible(TRUE);
    $defaults = $property->getValue($service);

    // YAML plugins don't specify their ID; it's generated automatically.
    unset($defaults['id']);

    $data['yaml_properties'] = $defaults;

    // The YAML discovery wraps another discovery object.
    $discovery_reflection = new \ReflectionClass($discovery);
    $property = $discovery_reflection->getProperty('discovery');
    $property->setAccessible(TRUE);
    $wrapped_discovery = $property->getValue($discovery);

    $wrapped_discovery_reflection = new \ReflectionClass($wrapped_discovery);
    $property = $wrapped_discovery_reflection->getProperty('name');
    $property->setAccessible(TRUE);
    $name = $property->getValue($wrapped_discovery);

    $data['yaml_file_suffix'] = $name;
  }

  /**
   * Analyse the plugin annotation class.
   *
   * This adds:
   *  - 'plugin_properties': The properties from the annotation class.
   *  - 'annotation_id_only': Boolean indicating whether the annotation consists
   *    of only the plugin ID.
   *
   * @param &$data
   *  The data for a single plugin type.
   */
  protected function addPluginAnnotationData(&$data) {
    $data['plugin_properties'] = [];

    if (!isset($data['plugin_definition_annotation_name'])) {
      return;
    }
    if (!class_exists($data['plugin_definition_annotation_name'])) {
      return;
    }

    // Detect whether the annotation is just an ID.
    if (is_a($data['plugin_definition_annotation_name'], \Drupal\Component\Annotation\PluginID::class, TRUE)) {
      $data['annotation_id_only'] = TRUE;
    }
    else {
      $data['annotation_id_only'] = FALSE;
    }

    // Get a reflection class for the annotation class.
    // Each property of the annotation class describes a property for the
    // plugin annotation.
    $annotation_reflection = new \ReflectionClass($data['plugin_definition_annotation_name']);
    $properties_reflection = $annotation_reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

    foreach ($properties_reflection as $property_reflection) {
      // Assemble data about this annotation property.
      $annotation_property_data = array();
      $annotation_property_data['name'] = $property_reflection->name;

      // Get the docblock for the property, so we can figure out whether the
      // annotation property requires translation, and also add detail to the
      // annotation code.
      $property_docblock = $property_reflection->getDocComment();
      $property_docblock_lines = explode("\n", $property_docblock);
      foreach ($property_docblock_lines as $line) {
        if (substr($line, 0, 3) == '/**') {
          continue;
        }

        // Take the first actual docblock line to be the description.
        if (!isset($annotation_property_data['description']) && substr($line, 0, 5) == '   * ') {
          $annotation_property_data['description'] = substr($line, 5);
        }

        // Look for a @var token, to tell us the type of the property.
        if (substr($line, 0, 10) == '   * @var ') {
          $annotation_property_data['type'] = substr($line, 10);
        }
      }

      $data['plugin_properties'][$property_reflection->name] = $annotation_property_data;
    }
  }

  /**
   * Determines the base class that plugins should use.
   *
   * @param &$data
   *  The data for a single plugin type.
   */
  protected function addPluginBaseClass(&$data) {
    $base_class = $this->analysePluginTypeBaseClass($data);
    if ($base_class) {
      $data['base_class'] = $base_class;
    }
  }

  /**
   * Analyses plugins of the given type to find a suitable base class.
   *
   * @param array $data
   *   The array of data for the plugin type.
   *
   * @return string
   *   The base class to use when generating plugins of this type.
   */
  protected function analysePluginTypeBaseClass(&$data) {
    // Work over each plugin of this type, finding a suitable candidate for
    // base class with each one.
    $potential_base_classes = [];

    $service = \Drupal::service($data['service_id']);
    $definitions = $service->getDefinitions();

    // Keep track of the classes we've seen, so we can skip derivative plugins
    // that have the same class.
    $plugin_classes = [];

    foreach ($definitions as $plugin_id => $definition) {
      if (is_array($definition)) {
        // We can't work with plugins that don't define a class: skip the whole
        // plugin type.
        if (empty($definition['class'])) {
          return;
        }

        $plugin_class = $definition['class'];
      }
      elseif ($definition instanceof Drupal\Component\Plugin\Definition\PluginDefinition) {
        $plugin_class = $definition->getClass();
      }
      else {
        // Skip the whole plugin type: no idea how to handle it.
        return;
      }

      // Skip classes we've seen already.
      if (isset($plugin_classes[$plugin_class])) {
        continue;
      }
      $plugin_classes[$plugin_class] = TRUE;

      // Skip any class that we can't attempt to load without a fatal. This will
      // typically happen if the plugin is meant for use with another module,
      // and inherits from a base class that doesn't exist in the current
      // codebase.
      if (!$this->codeAnalyser->classIsUsable($plugin_class)) {
        $data['broken_plugins'][$plugin_id] = 'crashes';

        continue;
      }

      // Babysit modules that have a broken plugin class. A number of things can
      // go wrong, including the namespace being incorrect for the file
      // location, preventing the class from being autoloaded.
      try {
        if (!class_exists($plugin_class)) {
          $data['broken_plugins'][$plugin_id] = 'no class';

          // Skip just this plugin.
          continue;
        }
      }
      catch (\Throwable $ex) {
        $data['broken_plugins'][$plugin_id] = 'exception';

        continue;
      }

      $plugin_component_namespace = $this->getClassComponentNamespace($plugin_class);

      // Get the full ancestry of the plugin's class.
      // Build a lineage array, from youngest to oldest, i.e. closest parents
      // first.
      $lineage = [];

      $plugin_class_reflection = new \ReflectionClass($plugin_class);

      $class_reflection = $plugin_class_reflection;
      while ($class_reflection = $class_reflection->getParentClass()) {
        $lineage[] = $class_reflection->getName();
      }

      // Try to get the nearest ancestor in the same namespace whose class
      // name ends in 'Base'.
      foreach ($lineage as $ancestor_class) {
        $parent_class_component_namespace = $this->getClassComponentNamespace($ancestor_class);
        if ($parent_class_component_namespace != $data['service_component_namespace']) {
          // No longer in the plugin manager's component: stop looking.
          break;
        }

        if (substr($ancestor_class, - strlen('Base')) == 'Base') {
          $potential_base_classes[] = $ancestor_class;

          // Done with this plugin.
          goto next_plugin;
        }
      } // foreach lineage

      // If we failed to find a class called FooBase in the same component.
      // Ue the youngest ancestor which is in the same component as the
      // plugin manager.
      // Getting the youngest ancestor accounts for modules that define
      // multiple plugin types and have a common base class for all of them
      // (e.g. Views), but unfortunately sometimes gets us a base class that
      // is too specialized in modules that provide several base classes,
      // for example a general base class and a more specific one. The next
      // step addresses that.
      foreach ($lineage as $ancestor_class) {
        $parent_class_component_namespace = $this->getClassComponentNamespace($ancestor_class);

        if ($parent_class_component_namespace == $data['service_component_namespace']) {
          // We've found an ancestor class in the plugin's hierarchy which is
          // in the same namespace as the plugin manager service. Assume it's
          // a good base class, and move on to the next plugin type.
          // TODO: don't take this if the class itself is a plugin.
          $potential_base_classes[] = $ancestor_class;

          // Done with this plugin.
          goto next_plugin;
        }
      } // foreach lineage

      // End of the loop for the current plugin.
      next_plugin:
        // This is a noop line to stop the PHP linting in my IDE from
        // complaining that this label has no statements, grrr...
        assert(true);
    } // foreach $definitions

    // If we found nothing, we're done.
    if (empty($potential_base_classes)) {
      return;
    }

    // We now have an array of several base classes, potentially as many as one
    // for each plugin.
    // Collapse this down to unique values.
    $potential_base_classes_unique = array_unique($potential_base_classes);

    // If that leaves us with only one base class, return that.
    if (count($potential_base_classes_unique) == 1) {
      return reset($potential_base_classes_unique);
    }

    // If we have more than one base class, it's most likely because there are
    // specialized base classes in addition to the main one (e.g. if our plugins
    // were animals, AnimalBase and FelineBase). Now organize the classes by
    // ancestry, and take the oldest one.

    // Try to form an ancestry chain of the potential base classes.
    // Start by partitioning the set of base classes into sets of classes that
    // are related by ancestry, so we can pick the largest set, and then sort
    // it by ancestry.
    $ancestry_sets = [];
    $current_set = [];
    foreach ($potential_base_classes_unique as $class) {
      // If the current set is empty, then the class goes in as it's trivially
      // related to itself.
      if (empty($current_set)) {
        $current_set[] = $class;
        continue;
      }

      // Get a sample class from the set. It doesn't matter which.
      $other_class = reset($current_set);

      if (is_subclass_of($class, $other_class) || is_subclass_of($other_class, $class)) {
        // If the current class is related to a class in the current set, add
        // it to the set.
        $current_set[] = $class;
      }
      else {
        // The current class is not related. Stash the current set, as we are
        // done with it, and start a new one.
        $ancestry_sets[] = $current_set;

        $current_set = [];
        $current_set[] = $class;
      }
    }
    // Once we're done with the loop, our current set remains: put it into the
    // partition list.
    $ancestry_sets[] = $current_set;

    // If there is more than one partition, take the largest.
    if (count($ancestry_sets) > 1) {
      $ancestry_sets_counts = [];
      foreach ($ancestry_sets as $index => $set) {
        $ancestry_sets_counts[$index] = count($set);
      }

      // Find the index of the largest set.
      // (If more than one are joint largest, it doesn't matter which one we
      // take as we have no way of choosing anyway.)
      // (Also note that in core, the only plugin type that produces more than
      // one set is archiver, which is hardly going to be used much.)
      $max_count = max($ancestry_sets_counts);
      $max_index = array_search($max_count, $ancestry_sets_counts);

      $set = $ancestry_sets[$max_index];
    }
    else {
      // There is only one set; take that.
      $set = $ancestry_sets[0];
    }

    // Sort the array of classes by parentage, youngest to oldest.
    usort($set, function($a, $b) {
      if (is_subclass_of($a, $b)) {
        return -1;
      }
      else {
        return 1;
      }
      // We filtered the array for uniqueness, so the 0 case won't ever happen,
      // and we partitioned the array, so we know the classes are always
      // related.
    });

    // Return the oldest parent, as this is the most generic of the found base
    // classes.
    $base_class = end($set);

    return $base_class;
  }

  /**
   * Deduces the config schema ID prefix for configurable plugin types.
   *
   * @param array $data
   *   The array of data for the plugin type.
   */
  protected function addConfigSchemaPrefix(&$data) {
    if (!is_subclass_of($data['plugin_interface'], \Drupal\Core\Field\PluginSettingsInterface::class)
      && !is_subclass_of($data['plugin_interface'], \Drupal\Component\Plugin\ConfigurablePluginInterface::class)
    ) {
      // Only look at configurable plugins, whose interface inherits from
      // ConfigurablePluginInterface.
      // (Field module uses the equivalent, deprecated PluginSettingsInterface.)
      return;
    }

    $service = \Drupal::service($data['service_id']);
    $plugin_definitions = $service->getDefinitions();
    $plugin_ids = array_keys($plugin_definitions);

    // Use the last piece of the service name to filter potential config schema
    // keys by. This is to prevent false positives from a plugin with the same
    // ID but of a different type. E.g., there is a config schema item
    // 'views.field.file_size' and a field.formatter plugin 'file_size'. By
    // expecting 'formatter' in config keys we filter this out.
    $service_pieces = explode('.', $data['service_id']);
    $last_service_piece = end($service_pieces);

    $typed_config_manager = \Drupal::service('config.typed');
    $config_schema_definitions = $typed_config_manager->getDefinitions();
    $config_schema_ids = array_keys($config_schema_definitions);

    $plugin_schema_roots = [];

    // Try to find a key for each plugin.
    foreach ($plugin_ids as $plugin_id) {
      // Get all schema IDs that end with the plugin ID.
      $candidate_schema_ids = preg_grep("@\.{$plugin_id}\$@", $config_schema_ids);

      // Further filter by the last piece of the plugin manager service ID.
      // E.g. for 'plugin.manager.field.formatter' we filter by 'formatter'.
      // Force a boundary before (rather than a '.'), as condition plugins have
      // schema of the form 'condition.plugin.request_path',
      // Don't force a '.' after, as filter plugins have schema of the form
      // 'filter_settings.filter_html'.
      $candidate_schema_ids = preg_grep("@\b{$last_service_piece}@", $candidate_schema_ids);

      if (count($candidate_schema_ids) != 1) {
        // Skip this plugin if we found no potential schema IDs, or if we found
        // more than one, so we don't deal with an ambiguous result.
        continue;
      }

      $plugin_schema_id = reset($candidate_schema_ids);

      // We are interested in the schema ID prefix, which we expect to find
      // before the plugin ID.
      $plugin_schema_root = preg_replace("@{$plugin_id}\$@", '', $plugin_schema_id);

      $plugin_schema_roots[] = $plugin_schema_root;
    }

    // We found no schema IDs for the plugins: nothing to do.
    if (empty($plugin_schema_roots)) {
      return;
    }

    // There's no common prefix: bail.
    if (count(array_unique($plugin_schema_roots)) != 1) {
      return;
    }

    $common_schema_prefix = reset($plugin_schema_roots);

    $data['config_schema_prefix'] = $common_schema_prefix;
  }

  /**
   * Adds list of methods for the plugin based on the plugin interface.
   *
   * @param &$data
   *  The data for a single plugin type.
   */
  protected function addPluginMethods(&$data) {
    // Set an empty array at the least.
    $data['plugin_interface_methods'] = [];

    if (empty($data['plugin_interface'])) {
      // If we didn't find an interface for the plugin, we can't do anything
      // here.
      return;
    }

    // Analyze the interface, if there is one.
    $reflection = new \ReflectionClass($data['plugin_interface']);
    $methods = $reflection->getMethods();

    $data['plugin_interface_methods'] = [];

    // Check each method from the plugin interface for suitability.
    foreach ($methods as $method_reflection) {
      // Start by assuming we won't include it.
      $include_method = FALSE;

      // Get the actual class/interface that declares the method, as the
      // plugin interface will in most cases inherit from one or more
      // interfaces.
      $declaring_class = $method_reflection->getDeclaringClass()->getName();
      // Get the namespace of the component the class belongs to.
      $declaring_class_component_namespace = $this->getClassComponentNamespace($declaring_class);

      if ($declaring_class_component_namespace == $data['service_component_namespace']) {
        // The plugin interface method is declared in the same component as
        // the plugin manager service.
        // Add it to the list of methods a plugin should implement.
        $include_method = TRUE;
      }
      else {
        // The plugin interface method is declared in a namespace other
        // than the plugin manager. Therefore it's something from a base
        // interface, e.g. from PluginInspectionInterface, and we shouldn't
        // add it to the list of methods a plugin should implement...
        // except for a few special cases.

        if ($declaring_class == 'Drupal\Core\Plugin\PluginFormInterface') {
          $include_method = TRUE;
        }
        if ($declaring_class == 'Drupal\Core\Cache\CacheableDependencyInterface') {
          $include_method = TRUE;
        }
      }

      if ($include_method) {
        $data['plugin_interface_methods'][$method_reflection->getName()] = $this->methodCollector->getMethodData($method_reflection);
      }
    }
  }

  /**
   * Adds data about the __construct() and create() methods for the plugins.
   *
   * The norm for plugins is to have three paramters for the constructor:
   *   $configuration, $plugin_id, $plugin_definition
   * A plugin that implements ContainerFactoryPluginInterface adds its injected
   * service parameters after these, and its create() method receives them and
   * passes them in. We thus have fixed parameters, and injection parameters.
   *
   * However, there are two ways in which a plugin type can deviate from this,
   * and which affect a plugin that injects services of its own:
   *  - The plugin base class already has injected services. In this case:
   *    - The create() method must extract the base class services and pass them
   *      in to the creation call.
   *    - The __construct() method must receive them as parameters after the
   *      basic parameters, and pass them up to the parent call.
   *  - The plugin manager service passes fixed parameters to the plugin base
   *    class which deviate from the basic parameters. In this case:
   *    - The create() method must pass these different parameters in the
   *      creation call. All the examples of this in core either pass a subset
   *      of the basic parameters, or pass extra parameters which are values in
   *      the $configuration array.
   *    - The __construct() method must receive the fixed parameters and pass
   *      them up to the parent call.
   *  - Fortunately, so far there are no known plugin types that combine
   *    both cases!
   *
   * To handle these cases, we detect the following:
   *  - parameters of the plugin base class's __construct() method.
   *  - values passed 'new static' call in the plugin base class's create()
   *    method.
   *
   * This allows generated plugins that have injected services to correctly
   * call the parent constructor, and pass the injected services to the base
   * class.
   *
   * @param &$data
   *  The data for a single plugin type.
   */
  protected function addConstructionData(&$data) {
    if (!isset($data['base_class'])) {
      // Can't do anything if we didn't find a base class.
      return;
    }

    // First see if the fixed parameters are non-standard.
    $fixed_parameters = $this->getFixedParametersFromManager($data['service_class_name']);
    if ($fixed_parameters) {
      // TODO: there is an unlikely (and not yet encountered) case where the
      // plugin manager implements createInstance() for other reasons, and
      // passes in the standard fixed parameters.

      foreach ($fixed_parameters as $i => $extraction) {
        $data['constructor_fixed_parameters'][$i]['extraction'] = $extraction;
      }
    }

    if (isset($data['constructor_fixed_parameters'])) {
      $fixed_parameter_count = count($data['constructor_fixed_parameters']);
    }
    else {
      $fixed_parameter_count = 3;
    }

    $base_class_construct_params_data = $this->getBaseClassConstructParameters($data, $fixed_parameter_count);
    if ($base_class_construct_params_data) {
      //dump($base_class_construct_params_data);
      foreach ($base_class_construct_params_data['fixed'] as $i => $param) {
        //dump($param);
        $data['constructor_fixed_parameters'][$i] += $param;
      }
    }

    $injected_parameters = \DrupalCodeBuilder\Utility\CodeAnalysis\DependencyInjection::getInjectedParameters($data['base_class'], $fixed_parameter_count);
    if ($injected_parameters) {
      $data['construction'] = $injected_parameters;
    }
  }

  /**
   * Get fixed parameters for plugin class __construct().
   *
   * Some plugin types have a constructor for the plugin classes with
   * non-standard parameters. In this case, the plugin manager will override
   * createInstance() to take care of passing them in. We need to be aware of
   * this because when generating a plugin with injected services, we implement
   * create() and __construct():
   * - create() needs to repeat the work of the plugin manager's
   *   createInstance() to get the right parameters to pass. Note that this can
   *   not be 100% accurate, as we can't detect whether createInstance() has any
   *   lines of code that prepare the variables to be passed to the constructor
   *   call.
   * - construct() needs to know the parameters that come before the injected
   *   services.
   *
   * @param [type] $plugin_type_data [description]
   */
  protected function getFixedParametersFromManager($service_class_name) {
    $method_ref = new \DrupalCodeBuilder\Utility\CodeAnalysis\Method($service_class_name, 'createInstance');
    if ($method_ref->getDeclaringClass()->getName() != $service_class_name) {
      // The plugin manager does not override createInstance(), therefore the
      // plugin class construct() method must have the normal parameters.
      return;
    }

    // Get the method's body code.
    // We could use the PHP parser library here rather than sniff with regexes,
    // but it would probably end up being just as much work poking around in
    // the syntax tree.
    $body = $method_ref->getBody();

    if (strpos($body, 'return new') === FALSE) {
      // The method doesn't construct an object: therefore the paramters for
      // the plugin constructor are the standard ones.
      return;
    }

    $matches = [];
    preg_match('@return new .+\((.+)\)@', $body, $matches);
    $construction_params = $matches[1];

    // Use preg_split() rather than explode() in case the code has formatting
    // errors or uses linebreaks.
    $construction_params = preg_split('@\s*,\s*@', $construction_params);

    //dump($construction_params);
    return $construction_params;
  }

  /**
   * Analyses the plugin base class's __construct() method parameters.
   *
   * Helper for addConstructionData().
   *
   * @param array $data
   *   The data for the plugin type.
   * @param int $fixed_parameter_count
   *   The number of fixed parameters for the __construct() method of plugin
   *   classes for this plugin type.
   *
   * @return
   *   An array containing data about the parameters, grouped into two arrays:
   *    - 'fixed': An array of the fixed parameters. This is left empty if the
   *      plugin type has the standard fixed parameters.
   *    - 'injection': An array of the injection parameters.
   *   Each item is itself a numerically indexed array where each item contains:
   *    - 'type': The typehint for the parameter.
   *    - 'name': The name of the parameter.
   */
  protected function getBaseClassConstructParameters($data, $fixed_parameter_count) {
    if (!method_exists($data['base_class'], '__construct')) {
      return;
    }

    // Get the parameter data from our reflection helper, then slice it into
    // two arrays, for fixed and injection.
    $construct_R = new \DrupalCodeBuilder\Utility\CodeAnalysis\Method($data['base_class'], '__construct');
    $parameter_data = $construct_R->getParamData();

    // Only set the fixed parameters if we know that they are non-standard.
    $fixed_parameters = [];
    if (isset($data['constructor_fixed_parameters'])) {
      $fixed_parameters = array_slice($parameter_data, 0, $fixed_parameter_count);
    }

    $return = [
      'fixed' => $fixed_parameters,
      'injection' => array_slice($parameter_data, $fixed_parameter_count),
    ];

    return $return;
  }

  /**
   * Adds plugin type information from Plugin module if present.
   *
   * @param &$plugin_type_data
   *  The array of data for all plugin types.
   */
  protected function addPluginModuleData(&$plugin_type_data) {
    // Bail if Plugin module isn't present.
    if (!\Drupal::hasService('plugin.plugin_type_manager')) {
      return;
    }

    // This gets us labels for the plugin types which are declared to Plugin
    // module.
    $plugin_types = \Drupal::service('plugin.plugin_type_manager')->getPluginTypes();

    // We need to re-key these by the service ID, as Plugin module uses IDs for
    // plugin types which don't always the ID we use for them based on the
    // plugin manager service ID, , e.g. views_access vs views.access.
    // Unfortunately, there's no accessor for this, so some reflection hackery
    // is required until https://www.drupal.org/node/2907862 is fixed.
    $reflection = new \ReflectionProperty(\Drupal\plugin\PluginType\PluginType::class, 'pluginManagerServiceId');
    $reflection->setAccessible(TRUE);

    foreach ($plugin_types as $plugin_type) {
      // Get the service ID from the reflection, and then our ID.
      $plugin_manager_service_id = $reflection->getValue($plugin_type);
      $plugin_type_id = substr($plugin_manager_service_id, strlen('plugin.manager.'));

      if (!isset($plugin_type_data[$plugin_type_id])) {
        continue;
      }

      // Replace the default label with the one from Plugin module, casting it
      // to a string so we don't have to deal with TranslatableMarkup objects.
      $plugin_type_data[$plugin_type_id]['type_label'] = (string) $plugin_type->getLabel();
    }
  }

  /**
   * Gets the namespace for the component a class is in.
   *
   * This is either a module namespace, or a core component namespace, e.g.:
   *  - 'Drupal\foo'
   *  - 'Drupal\Core\Foo'
   *  - 'Drupal\Component\Foo'
   *
   * @param string $class_name
   *  The class name.
   *
   * @return string
   *  The namespace, or an empty string if nothing found.
   */
  protected function getClassComponentNamespace($class_name) {
    $pieces = explode('\\', $class_name);

    if (!isset($pieces[1])) {
      // We don't know about something that is in the global namespace.
      return '';
    }

    if ($pieces[1] == 'Core' || $pieces[1] == 'Component') {
      return implode('\\', array_slice($pieces, 0, 3));
    }
    else {
      return implode('\\', array_slice($pieces, 0, 2));
    }
  }

}
