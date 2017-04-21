<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Collect8.
 */

namespace DrupalCodeBuilder\Task;

/**
 * Task handler for collecting and processing component definitions.
 *
 * This collects data on hooks and plugin types.
 */
class Collect8 extends Collect {

  /**
   * Collect data about Drupal components from the current site's codebase.
   */
  public function collectComponentData() {
    $this->collectHooks();
    $this->collectPlugins();
    $this->collectServices();
  }

  /**
   * Collect data about plugin types and process it.
   */
  protected function collectPlugins() {
    $plugin_manager_service_ids = $this->getPluginManagerServices();

    $plugin_type_data = $this->gatherPluginTypeInfo($plugin_manager_service_ids);

    // Save the data.
    $this->writeProcessedData($plugin_type_data, 'plugins');
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
    // Get plugin type information if Plugin module is present.
    // This gets us labels for some plugin types (though not all, as the plugin
    // type ID used by Plugin module doesn't always match the ID we get from
    // the service definition, e.g. views_access vs views.access).
    if (\Drupal::hasService('plugin.plugin_type_manager')) {
      $plugin_types = \Drupal::service('plugin.plugin_type_manager')->getPluginTypes();
    }

    // Assemble data from each plugin manager.
    $plugin_type_data = array();
    foreach ($plugin_manager_service_ids as $plugin_manager_service_id) {
      // We identify plugin types by the part of the plugin manager service name
      // that comes after 'plugin.manager.'.
      $plugin_type_id = substr($plugin_manager_service_id, strlen('plugin.manager.'));

      $data = [
        'type_id' => $plugin_type_id,
        'type_label' => isset($plugin_types[$plugin_type_id]) ?
          $plugin_types[$plugin_type_id]->getLabel() : $plugin_type_id,
        'service_id' => $plugin_manager_service_id,
      ];

      // Get the service, and then get the properties that the plugin manager
      // constructor sets.
      // E.g., most plugin managers pass this to the parent:
      //   parent::__construct('Plugin/Block', $namespaces, $module_handler, 'Drupal\Core\Block\BlockPluginInterface', 'Drupal\Core\Block\Annotation\Block');
      // See Drupal\Core\Plugin\DefaultPluginManager
      $service = \Drupal::service($plugin_manager_service_id);
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

      // Analyze the interface, if there is one.
      if (empty($data['plugin_interface'])) {
        $data['plugin_interface_methods'] = array();
      }
      else {
        $data['plugin_interface_methods'] = $this->collectPluginInterfaceMethods($data['plugin_interface']);
      }

      // Now analyze the anotation.
      if (isset($data['plugin_definition_annotation_name']) && class_exists($data['plugin_definition_annotation_name'])) {
        $data['plugin_properties'] = $this->collectPluginAnnotationProperties($data['plugin_definition_annotation_name']);
      }
      else {
        $data['plugin_properties'] = [];
      }

      $plugin_type_data[$plugin_type_id] = $data;
    }

    // Sort by ID.
    ksort($plugin_type_data);

    //drush_print_r($plugin_type_data);

    return $plugin_type_data;
  }

  /**
   * Get data for the methods of a plugin interface.
   *
   * Helper for gatherPluginTypeInfo().
   *
   * @param $plugin_interface
   *  The fully-qualified name of the interface.
   *
   * @return
   *  An array keyed by method name, where each value is an array containing:
   *  - 'name: The name of the method.
   *  - 'declaration': The function declaration line.
   *  - 'description': The description from the method's docblock first line.
   */
  protected function collectPluginInterfaceMethods($plugin_interface) {
    // Get a reflection class for the interface.
    $plugin_interface_reflection = new \ReflectionClass($plugin_interface);
    $methods = $plugin_interface_reflection->getMethods();

    $data = [];

    foreach ($methods as $method) {
      $interface_method_data = [];

      $interface_method_data['name'] = $method->getName();

      // Methods may be in parent interfaces, so not all in the same file.
      $filename = $method->getFileName();
      $source = file($filename);
      $start_line = $method->getStartLine();

      // Trim whitespace from the front, as this will be indented.
      $interface_method_data['declaration'] = trim($source[$start_line - 1]);

      // Get the docblock for the method.
      $method_docblock_lines = explode("\n", $method->getDocComment());
      foreach ($method_docblock_lines as $line) {
        // Take the first actual docblock line to be the description.
        if (substr($line, 0, 5) == '   * ') {
          $interface_method_data['description'] = substr($line, 5);
          break;
        }
      }

      $data[$method->getName()] = $interface_method_data;
    }

    return $data;
  }

  /**
   * Get the list of properties from an annotation class.
   *
   * Helper for gatherPluginTypeInfo().
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
   * Collect data about services.
   */
  protected function collectServices() {
    $service_definitions = $this->gatherServiceDefinitions();

    // Save the data.
    $this->writeProcessedData($service_definitions, 'services');
  }

  /**
   * Get definitions of services from the static container.
   *
   * We collect an incomplete list of services, namely, those which have special
   * methods in the \Drupal static container. This is because (AFAIK) these are
   * the only ones for which we can detect the interface and a description.
   */
  protected function gatherServiceDefinitions() {
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

      $service_definition = [
        'id' => $service_id,
        'label' => $label,
        'static_method' => $name,
        'interface' => $interface,
        'description' => $description,
      ];
      $service_definitions[$service_id] = $service_definition;
    }

    // Sort by ID.
    ksort($service_definitions);

    return $service_definitions;
  }

  /**
   * Gather hook documentation files.
   *
   * This retrieves a list of api hook documentation files from the current
   * Drupal install. On D8 these are files of the form MODULE.api.php and are
   * present in the codebase (rather than needing to be downloaded from an
   * online code repository viewer as is the case in previous versions of
   * Drupal).
   */
  protected function gatherHookDocumentationFiles() {
    // Get the hooks directory.
    $directory = \DrupalCodeBuilder\Factory::getEnvironment()->getHooksDirectory();

    // Get Drupal root folder as a file path.
    // DRUPAL_ROOT is defined both by Drupal and Drush.
    // @see _drush_bootstrap_drupal_root(), index.php.
    $drupal_root = DRUPAL_ROOT;

    $system_listing = \DrupalCodeBuilder\Factory::getEnvironment()->systemListing('/\.api\.php$/', 'modules', 'filename');
    // returns an array of objects, properties: uri, filename, name,
    // keyed by filename, eg 'comment.api.php'
    // What this does not give us is the originating module!

    // Add in api.php files in core/lib.
    $core_directory = new \RecursiveDirectoryIterator('core/lib/Drupal');
    $iterator = new \RecursiveIteratorIterator($core_directory);
    $regex = new \RegexIterator($iterator, '/^.+\.api.php$/i', \RecursiveRegexIterator::GET_MATCH);
    $core_api_files = [];
    foreach ($regex as $regex_files) {
      foreach ($regex_files as $file) {
        $filename = basename($file);

        $component_name = explode('.', $filename)[0];
        $system_listing['core:' . $filename] = (object) array(
          'uri' => $file,
          'filename' => $filename,
          'name' => basename($file, '.php'),
          'group' => 'core:' . $component_name,
          'module' => 'core',
        );
      }
    }

    // Add in core.api.php, which won't have been picked up because it's not
    // in a module!
    $system_listing['core.api.php'] = (object) array(
      'uri' => 'core/core.api.php',
      'filename' => 'core.api.php',
      'name' => 'core.api',
      'group' => 'core:core',
      'module' => 'core',
    );

    //print_r($system_listing);

    foreach ($system_listing as $key => $file) {
      // Extract the module name from the path.
      // WARNING: this is not always going to be correct: will fail in the
      // case of submodules. So Commerce is a big problem here.
      // We could instead assume we have MODULE.api.php, but some modules
      // have multiple API files with suffixed names, eg Services.
      // @todo: make this more robust, somehow!
      if (!isset($file->module)) {
        $matches = array();
        preg_match('@modules/(?:contrib/)?(\w+)@', $file->uri, $matches);
        //print_r($matches);
        $file->module = $matches[1];
        $file->group = $file->module;
      }
      //dsm($matches, $module);

      // Mark core files.
      $core = (substr($file->uri, 0, 4) == 'core');

      // Copy the file to the hooks directory.
      copy($drupal_root . '/' . $file->uri, $directory . '/' . $file->filename);

      $hook_files[$key] = array(
        'original' => $drupal_root . '/' . $file->uri, // no idea if useful
        'path' => $directory . '/' . $file->filename,
        'destination' => '%module.module', // Default. We override this below.
        'group'       => $file->group,
        'module'      => $file->module,
        'core'        => $core,
      );
    }

    // We now have the basics.
    // We should now see if some modules have extra information for us.
    $this->getHookDestinations($hook_files);

    return $hook_files;
  }

  /**
   * Add extra data about hook destinations to the hook file data.
   *
   * This allows entire files or individual hooks to have a file other than
   * the default %module.module as their destination.
   */
  private function getHookDestinations(&$hook_files) {
    // Get our data.
    $data = $this->getHookInfo();

    // Incoming data is destination key, array of hooks.
    // (Because it makes typing the data out easier! Computers can just adapt.)
    foreach ($data as $module => $module_data) {
      // The key in $hook_files we correspond to
      // @todo, possibly: this feels like slightly shaky ground.
      $filename = "$module.api.php";

      // Skip filenames we haven't already found, so we don't pollute our data
      // array with hook destination data for files that don't exist here.
      if (!isset($hook_files[$filename])) {
        continue;
      }

      // The module data can set a single destination for all its hooks.
      if (isset($module_data['destination'])) {
        $hook_files[$filename]['destination'] = $module_data['destination'];
      }
      // It can also (or instead) set a destination per hook.
      if (isset($module_data['hook_destinations'])) {
        $hook_files[$filename]['hook_destinations'] = array();
        foreach ($module_data['hook_destinations'] as $destination => $hooks) {
          $destinations[$module] = array_fill_keys($hooks, $destination);
          $hook_files[$filename]['hook_destinations'] += array_fill_keys($hooks, $destination);
        }
      }

      // Add the dependencies array as it comes; it will be processed per hook later.
      if (isset($module_data['hook_dependencies'])) {
        $hook_files[$filename]['hook_dependencies'] = $module_data['hook_dependencies'];
      }
    }

    //print_r($hook_files);
  }

  /**
   * Get info about hooks from Drupal.
   *
   * @return
   *  The data from hook_hook_info().
   */
  protected function getDrupalHookInfo() {
    $hook_info = \Drupal::service('module_handler')->getHookInfo();
    return $hook_info;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdditionalHookInfo() {
    // Keys should match the filename MODULE.api.php
    $info = array(
      // Hooks on behalf of Drupal core.
      // api.php files that are in core rather than in a module have a prefix of
      // 'core:'.
      'core:module' => array(
        'hook_destinations' => array(
          '%module.install' => array(
            'hook_requirements',
            'hook_schema',
            'hook_schema_alter',
            'hook_install',
            'hook_update_N',
            'hook_update_last_removed',
            'hook_uninstall',
          ),
        ),
      ),
    );
    return $info;
  }

}
