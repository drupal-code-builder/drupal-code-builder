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
   *  Helper objects.
   *
   * @var array
   */
  protected $helpers = [];

  /**
   * {@inheritdoc}
   */
  public function collectComponentData() {
    $result = $this->collectHooks();
    $result += $this->collectPlugins();
    $result += $this->collectServices();
    $result += $this->collectServiceTagTypes();
    $result += $this->collectFieldTypes();
    $result += $this->collectDataTypes();

    return $result;
  }

  /**
   * Collect data about plugin types and process it.
   *
   * @return
   *  A summary in the same format as returned by collectComponentData().
   */
  protected function collectPlugins() {
    $plugin_type_data = $this->getHelper('PluginTypesCollector')->collect();

    // Save the data.
    $this->environment->getStorage()->store('plugins', $plugin_type_data);

    return ['plugin types' => count($plugin_type_data)];
  }

  /**
   * Collect data about services.
   *
   * @return
   *  A summary in the same format as returned by collectComponentData().
   */
  protected function collectServices() {
    $service_definitions = $this->getHelper('ServicesCollector')->collect();

    // Save the data.
    $this->environment->getStorage()->store('services', $service_definitions);

    return ['services' => count($service_definitions['all'])];
  }

  /**
   * Collect data about tagged service types.
   *
   * @return
   *  A summary in the same format as returned by collectComponentData().
   */
  protected function collectServiceTagTypes() {
    $service_tag_type_definitions = $this->getHelper('ServiceTagTypesCollector')->collectServiceTagTypes();

    // Save the data.
    $this->environment->getStorage()->store('service_tag_types', $service_tag_type_definitions);

    return ['tagged service types' => count($service_tag_type_definitions)];
  }

  /**
   * Collect data about field_type types.
   *
   * @return
   *  A summary in the same format as returned by collectComponentData().
   */
  protected function collectFieldTypes() {
    $field_type_definitions = $this->getHelper('FieldTypesCollector')->collectFieldTypes();

    // Save the data.
    $this->environment->getStorage()->store('field_types', $field_type_definitions);

    return ['field types' => count($field_type_definitions)];
  }

  /**
   * Collect data about config data types.
   *
   * @return
   *  A summary in the same format as returned by collectComponentData().
   */
  protected function collectDataTypes() {
    $data_type_definitions = $this->getHelper('DataTypesCollector')->collect();

    // Save the data.
    $this->environment->getStorage()->store('data_types', $data_type_definitions);

    return ['data types' => count($data_type_definitions)];
  }

  /**
   * Gather hook documentation files.
   *
   * This retrieves a list of api hook documentation files from the current
   * Drupal install. On D8 these are files of the form MODULE.api.php and are
   * present in the codebase (rather than needing to be downloaded from an
   * online code repository viewer as is the case in previous versions of
   * Drupal).
   *
   * Because Drupal 8 puts api.php files in places other than module folders,
   * keys of the return array may be in one of these forms:
   *  - foo.api.php: The API file for foo module.
   *  - core:foo.api.php: The API file in a Drupal component.
   *  - core.api.php: The single core.api.php file.
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

  /**
   * Returns the helper for the given short class name.
   *
   * @param $class
   *   The short class name.
   *
   * @return
   *   The helper object.
   */
  protected function getHelper($class) {
    if (!isset($this->helpers[$class])) {
      $qualified_class = '\DrupalCodeBuilder\Task\Collect\\' . $class;

      switch ($class) {
        case 'PluginTypesCollector':
          $helper = new $qualified_class(
            $this->environment,
            $this->getHelper('MethodCollector'),
            $this->getHelper('CodeAnalyser')
          );
          break;
        case 'ServiceTagTypesCollector':
          $helper = new $qualified_class(
            $this->environment,
            $this->getHelper('ContainerBuilderGetter'),
            $this->getHelper('MethodCollector')
          );
          break;
        case 'ServicesCollector':
          $helper = new $qualified_class(
            $this->environment,
            $this->getHelper('ContainerBuilderGetter'),
            $this->getHelper('CodeAnalyser')
          );
          break;
        default:
          $helper = new $qualified_class($this->environment);
          break;
      }

      $this->helpers[$class] = $helper;
    }

    return $this->helpers[$class];
  }

}
