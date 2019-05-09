<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Task helper for collecting data on hooks on Drupal 8.
 */
class HooksCollector8 extends HooksCollector {

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    $system_listing = \DrupalCodeBuilder\Factory::getEnvironment()->systemListing('/\.api\.php$/', 'modules', 'filename');

    // Convert the file objects into arrays.
    $api_files = [];
    // Keep the key for now so we can sort by it.
    foreach ($system_listing as $filename => $file) {
      $api_files[$filename] = (array) $file;

      $api_files[$filename]['process_label'] = 'hooks';
      $api_files[$filename]['item_label'] = $filename;
    }

    // Add in api.php files in core/lib.
    $core_directory = new \RecursiveDirectoryIterator('core/lib/Drupal');
    $iterator = new \RecursiveIteratorIterator($core_directory);
    $regex = new \RegexIterator($iterator, '/^.+\.api.php$/i', \RecursiveRegexIterator::GET_MATCH);
    $core_api_files = [];
    foreach ($regex as $regex_files) {
      foreach ($regex_files as $file) {
        $filename = basename($file);

        $component_name = explode('.', $filename)[0];
        $api_files['core:' . $filename] = array(
          'uri' => $file,
          // Prefix the filename, to prevent file.api.php that's in core/lib
          // clobbering the one for file module (and any other such WTFs that
          // come up in future).
          'filename' => 'CORE_' . $filename,
          'name' => basename($file, '.php'),
          'group' => 'core:' . $component_name,
          'module' => 'core',
          'process_label' => 'hooks',
          'item_label' => $filename,
        );
      }
    }

    // Add in core.api.php, which won't have been picked up because it's not
    // in a module!
    $api_files['core.api.php'] = array(
      'uri' => 'core/core.api.php',
      'filename' => 'CORE_core.api.php',
      'name' => 'core.api',
      'group' => 'core:core',
      'module' => 'core',
      'process_label' => 'hooks',
      'item_label' => 'core.api.php',
    );

    // Sort by the key, which is the filename for module files, and the group
    // for core files.
    ksort($api_files);

    // Strip the key out for the job list.
    return array_values($api_files);
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
  protected function gatherHookDocumentationFiles($api_files) {
    // Get the hooks directory.
    $directory = \DrupalCodeBuilder\Factory::getEnvironment()->getHooksDirectory();

    // Get Drupal root folder as a file path.
    // DRUPAL_ROOT is defined both by Drupal and Drush.
    // @see _drush_bootstrap_drupal_root(), index.php.
    $drupal_root = DRUPAL_ROOT;

    $hook_files = [];
    foreach ($api_files as $file) {
      $filename = $file['filename'];

      // Extract the module name from the path.
      // WARNING: this is not always going to be correct: will fail in the
      // case of submodules. So Commerce is a big problem here.
      // We could instead assume we have MODULE.api.php, but some modules
      // have multiple API files with suffixed names, eg Services.
      // @todo: make this more robust, somehow!
      if (!isset($file['module'])) {
        $matches = array();
        preg_match('@modules/(?:contrib/)?(\w+)@', $file['uri'], $matches);
        //print_r($matches);
        $file['module'] = $matches[1];
        $file['group'] = $file['module'];
      }
      //dsm($matches, $module);

      // Mark core files.
      $core = (substr($file['uri'], 0, 4) == 'core');

      $hook_files[$filename] = array(
        'original' => $drupal_root . '/' . $file['uri'], // no idea if useful
        'path' => $directory . '/' . $file['filename'],
        'destination' => '%module.module', // Default. We override this below.
        'group'       => $file['group'],
        'module'      => $file['module'],
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
    $hook_destinations = $this->getHookInfo();

    // Incoming data is destination key, array of hooks.
    // (Because it makes typing the data out easier! Computers can just adapt.)
    foreach ($hook_destinations as $module => $module_data) {
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
      // 'CORE_'.
      // TODO: clarify and document what this key represents. It's sort of the
      // api file basename, unless it's a file from core components rather than
      // a module.
      'CORE_database' => [
        'hook_destinations' => [
          '%module.install' => [
            'hook_schema',
            'hook_schema_alter',
          ],
        ],
      ],
      'CORE_module' => array(
        'hook_destinations' => array(
          '%module.install' => array(
            'hook_requirements',
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
