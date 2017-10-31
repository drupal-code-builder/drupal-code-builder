<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Task helper for collecting data on plugin types.
 */
class PluginTypesCollector {

  /**
   * The method collector helper
   */
  protected $methodCollector;

  /**
   * The names of plugin type managers to collect for testing sample data.
   */
  protected $testingPluginManagerServiceIds = [
    'plugin.manager.block',
    'plugin.manager.field.formatter',
  ];

  /**
   * Constructs a new helper.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment object.
   * @param MethodCollector $method_collector
   *   The method collector helper.
   */
  public function __construct(
    EnvironmentInterface $environment,
    MethodCollector $method_collector
  ) {
    $this->environment = $environment;
    $this->methodCollector = $method_collector;
  }

  /**
   * Get definitions of services.
   *
   * @return array
   */
  public function collect() {
    $plugin_manager_service_ids = $this->getPluginManagerServices();

    // Filter for testing sample data collection.
    if (!empty($this->environment->sample_data_write)) {
      // Note this is not an intersect on keys like the other collectors!
      $plugin_manager_service_ids = array_intersect($plugin_manager_service_ids, $this->testingPluginManagerServiceIds);
    }

    //$plugin_manager_service_ids = ['plugin.manager.block'];

    $plugin_type_data = $this->gatherPluginTypeInfo($plugin_manager_service_ids);

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
    // Get the IDs of all services from the container.
    $service_ids = \Drupal::getContainer()->getServiceIds();
    //drush_print_r($service_ids);

    // Filter them down to the ones that are plugin managers.
    // TODO: this omits some that don't conform to this pattern! Deal with
    // these! See https://www.drupal.org/node/2086181
    $plugin_manager_service_ids = array_filter($service_ids, function($element) {
      if (strpos($element, 'plugin.manager.') === 0) {
        return TRUE;
      }
    });

    //drush_print_r($plugin_manager_service_ids);

    // Developer trapdoor: just process the block plugin type, to make terminal
    // debug output easier to read through.
    //$plugin_manager_service_ids = array('plugin.manager.block');

    return $plugin_manager_service_ids;
  }

  /**
   * Detects information about plugin types from the plugin manager services
   *
   * @param $plugin_manager_service_ids
   *  An array of service IDs.
   *
   * @return
   *  The assembled plugin type data. This is an array keyed by plugin type ID
   *  (where we take this to be the name of the plugin manager service for that
   *  type, with the 'plugin.manager.' prefix removed). Values are arrays with
   *  the following properties:
   *    - 'type_id': The plugin type ID.
   *    - 'type_label': A label for the plugin type. If Plugin module is present
   *      then this is the label from the definition there, if found. Otherwise,
   *      this duplicates the ID.
   *    - 'service_id': The ID of the service for the plugin type's manager.
   *    - 'subdir: The subdirectory of /src that plugin classes must go in.
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
   *
   *  Due to the difficult nature of analysing the code for plugin types, some
   *  of these properties may be empty if they could not be deduced.
   */
  protected function gatherPluginTypeInfo($plugin_manager_service_ids) {
    // Assemble a basic array of plugin type data, that we will successively add
    // data to.
    $plugin_type_data = array();
    foreach ($plugin_manager_service_ids as $plugin_manager_service_id) {
      // We identify plugin types by the part of the plugin manager service name
      // that comes after 'plugin.manager.'.
      $plugin_type_id = substr($plugin_manager_service_id, strlen('plugin.manager.'));

      // Get the class name for the service.
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
    }

    // Get plugin type information if Plugin module is present.
    $this->addPluginModuleData($plugin_type_data);

    // Add data from the plugin type manager service.
    // This gets us the subdirectory, interface, and annotation name.
    $this->addPluginTypeServiceData($plugin_type_data);

    // Add data from the plugin annotation class.
    $this->addPluginAnnotationData($plugin_type_data);

    // Try to detect a base class for plugins
    $this->addPluginBaseClass($plugin_type_data);

    // Add data from the plugin interface (which the manager service gave us).
    $this->addPluginMethods($plugin_type_data);

    // Sort by ID.
    ksort($plugin_type_data);

    //drush_print_r($plugin_type_data);

    return $plugin_type_data;
  }

  /**
   * Adds plugin type information from Plugin module if present.
   *
   * @param &$plugin_type_data
   *  The array of plugin data.
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
        return;
      }

      // Replace the default label with the one from Plugin module, casting it
      // to a string so we don't have to deal with TranslatableMarkup objects.
      $plugin_type_data[$plugin_type_id]['type_label'] = (string) $plugin_type->getLabel();
    }
  }

  /**
   * Adds plugin type information from each plugin type manager service.
   *
   * This adds:
   *  - subdir
   *  - pluginInterface
   *  - pluginDefinitionAnnotationName
   *
   * @param &$plugin_type_data
   *  The array of plugin data.
   */
  protected function addPluginTypeServiceData(&$plugin_type_data) {
    foreach ($plugin_type_data as $plugin_type_id => &$data) {
      // Get the service, and then get the properties that the plugin manager
      // constructor sets.
      // E.g., most plugin managers pass this to the parent:
      //   parent::__construct('Plugin/Block', $namespaces, $module_handler, 'Drupal\Core\Block\BlockPluginInterface', 'Drupal\Core\Block\Annotation\Block');
      // See Drupal\Core\Plugin\DefaultPluginManager
      $service = \Drupal::service($data['service_id']);
      $reflection = new \ReflectionClass($service);

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
  }

  /**
   * Adds list of methods for the plugin based on the plugin interface.
   *
   * @param &$plugin_type_data
   *  The array of plugin data.
   */
  protected function addPluginMethods(&$plugin_type_data) {
    foreach ($plugin_type_data as $plugin_type_id => &$data) {
      // Set an empty array at the least.
      $data['plugin_interface_methods'] = [];

      if (empty($data['plugin_interface'])) {
        // If we didn't find an interface for the plugin, we can't do anything
        // here.
        continue;
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
  }

  /**
   * Adds plugin type information from the plugin annotation class.
   *
   * @param &$plugin_type_data
   *  The array of plugin data.
   */
  protected function addPluginAnnotationData(&$plugin_type_data) {
    foreach ($plugin_type_data as $plugin_type_id => &$data) {
      if (isset($data['plugin_definition_annotation_name']) && class_exists($data['plugin_definition_annotation_name'])) {
        $data['plugin_properties'] = $this->collectPluginAnnotationProperties($data['plugin_definition_annotation_name']);
      }
      else {
        $data['plugin_properties'] = [];
      }
    }
  }

  /**
   * Get the list of properties from an annotation class.
   *
   * Helper for addPluginAnnotationData().
   *
   * @param $plugin_annotation_class
   *  The fully-qualified name of the plugin annotation class.
   *
   * @return
   *  An array keyed by property name, where each value is an array containing:
   *  - 'name: The name of the property.
   *  - 'description': The description from the property's docblock first line.
   */
  protected function collectPluginAnnotationProperties($plugin_annotation_class) {
    // Get a reflection class for the annotation class.
    // Each property of the annotation class describes a property for the
    // plugin annotation.
    $annotation_reflection = new \ReflectionClass($plugin_annotation_class);
    $properties_reflection = $annotation_reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

    $plugin_properties = [];
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

      $plugin_properties[$property_reflection->name] = $annotation_property_data;
    }

    return $plugin_properties;
  }

  /**
   * Adds plugin type information from the plugin annotation TODO!.
   *
   * @param &$plugin_type_data
   *  The array of plugin data.
   */
  protected function addPluginBaseClass(&$plugin_type_data) {
    foreach ($plugin_type_data as $plugin_type_id => &$data) {
      // Work over each plugin of this type, until we find one with a suitable-
      // looking ancestor class.
      $service = \Drupal::service($data['service_id']);
      $definitions = $service->getDefinitions();
      foreach ($definitions as $plugin_id => $definition) {
        // We can't work with plugins that don't define a class: skip the whole
        // plugin type.
        if (empty($definition['class'])) {
          goto done_plugin_type;
        }

        $plugin_component_namespace = $this->getClassComponentNamespace($definition['class']);

        // Get the full ancestry of the plugin's class.
        // Build a lineage array, from youngest to oldest, i.e. closest parents
        // first.
        $lineage = [];
        $plugin_class_reflection = new \ReflectionClass($definition['class']);
        $class_reflection = $plugin_class_reflection;
        while ($class_reflection = $class_reflection->getParentClass()) {
          $lineage[] = $class_reflection->getName();
        }

        // We want the oldest ancestor which is in the same namespace as the
        // plugin manager. The lineage array has the oldest ancestors last.
        while ($ancestor_class = array_pop($lineage)) {
          $parent_class_component_namespace = $this->getClassComponentNamespace($ancestor_class);

          if ($parent_class_component_namespace == $data['service_component_namespace']) {
            // We've found an ancestor class in the plugin's hierarchy which is
            // in the same namespace as the plugin manager service. Assume it's
            // a good base class, and move on to the next plugin type.
            $data['base_class'] = $ancestor_class;

            // TODO: should we check more than the first plugin we find?

            goto done_plugin_type;
          }
        }
      }

      // Done with this plugin definition; move on to the next one.
      done_plugin_type:
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
   *  The namespace.
   */
  protected function getClassComponentNamespace($class_name) {
    $pieces = explode('\\', $class_name);

    if ($pieces[1] == 'Core' || $pieces[1] == 'Component') {
      return implode('\\', array_slice($pieces, 0, 3));
    }
    else {
      return implode('\\', array_slice($pieces, 0, 2));
    }
  }

}
