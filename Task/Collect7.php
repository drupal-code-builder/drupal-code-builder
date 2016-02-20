<?php

/**
 * @file
 * Contains ModuleBuilder\Task\Collect7.
 */

namespace ModuleBuilder\Task;

/**
 * Task handler for collecting and processing hook definitions.
 */
class Collect7 extends Collect {

  /**
   * Gather hook documentation files.
   *
   * This retrieves a list of api hook documentation files from the current
   * Drupal install. On D7 these are files of the form MODULE.api.php and are
   * present in the codebase (rather than needing to be downloaded from an
   * online code repository viewer as is the case in previous versions of
   * Drupal).
   */
  protected function gatherHookDocumentationFiles() {
    // Get the hooks directory.
    $directory = \ModuleBuilder\Factory::getEnvironment()->getHooksDirectory();

    // Get Drupal root folder as a file path.
    // DRUPAL_ROOT is defined both by Drupal and Drush.
    // @see _drush_bootstrap_drupal_root(), index.php.
    $drupal_root = DRUPAL_ROOT;

    $system_listing = \ModuleBuilder\Factory::getEnvironment()->systemListing('/\.api\.php$/', 'modules', 'filename');
    // returns an array of objects, properties: uri, filename, name,
    // keyed by filename, eg 'comment.api.php'
    // What this does not give us is the originating module!

    //print_r($system_listing);

    foreach ($system_listing as $filename => $file) {
      // Extract the module name from the path.
      // WARNING: this is not always going to be correct: will fail in the
      // case of submodules. So Commerce is a big problem here.
      // We could instead assume we have MODULE.api.php, but some modules
      // have multiple API files with suffixed names, eg Services.
      // @todo: make this more robust, somehow!
      $matches = array();
      preg_match('@modules/(?:contrib/)?(\w+)@', $file->uri, $matches);
      //print_r($matches);
      $module = $matches[1];
      //dsm($matches, $module);

      // Copy the file to the hooks directory.
      copy($drupal_root . '/' . $file->uri, $directory . '/' . $file->filename);

      $hook_files[$filename] = array(
        'original' => $drupal_root . '/' . $file->uri, // no idea if useful
        'path' => $directory . '/' . $file->filename,
        'destination' => '%module.module', // Default. We override this below.
        'group'       => $module, // @todo specialize this?
        'module'      => $module,
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
   *
   * @see module_builder_module_builder_info().
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
   * {@inheritdoc}
   */
  protected function getAdditionalHookInfo() {
    // For D7, keys should match the filename MODULE.api.php
    $info = array(
      // Hooks on behalf of Drupal core.
      'system' => array(
        'hook_destinations' => array(
          '%module.install' => array(
            'hook_requirements',
            'hook_schema',
            'hook_schema_alter',
            'hook_install',
            'hook_update_N',
            'hook_update_last_removed',
            'hook_uninstall',
            'hook_enable',
            'hook_disable',
          ),
        ),
      ),
      'block' => array(
        'hook_dependencies' => array(
          'hook_block_info' => array(
            'hook_block_*',
          ),
        ),
      ),
      'field' => array(
        'hook_destinations' => array(
          '%module.install' => array(
            'hook_field_schema',
          ),
        ),
      ),
      // Hooks in theme.api.php. This does nothing yet!
      'theme' => array(
        'destination' => 'template.php',
      ),
      // Views
      'views' => array(
        'hook_destinations' => array(
          '%module.views.inc' => array(
            'hook_views_data',
            'hook_views_data_alter',
            'hook_views_plugins',
            'hook_views_plugins_alter',
            'hook_views_query_alter',
          ),
          '%module.views_default.inc' => array(
            'hook_views_default_views',
          ),
        ),
        // Data about hook dependencies.
        'hook_dependencies' => array(
          // A required hook.
          'hook_views_api' => array(
            // An array of hooks that require this, as a regex.
            // TODO!??? dependencies across different API files not yet supported!
            'hook_views_.*',
          ),
        ),
      ),
      'rules' => array(
        'hook_destinations' => array(
          '%module.rules.inc' => array(
            'hook_rules_action_info',
          ),
          '%module.rules_default.inc' => array(
            'hook_default_rules_configuration',
          ),
        ),
      ),
    );
    return $info;
  }

}
