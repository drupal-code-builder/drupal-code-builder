<?php

/**
 * @file
 * Definition of ModuleBuider\Task\Collect8.
 */

namespace ModuleBuider\Task;

/**
 * Task handler for collecting and processing hook definitions.
 */
class Collect8 extends Collect {

  /**
   * Gather hook documentation files.
   *
   * This retrieves a list of api hook documentation files from the current
   * Drupal install. On D8 these are files of the form MODULE.api.php and are
   * present in the codebase (rather than needing to be downloaded from an
   * online code repository viewer as is the case in previous versions of
   * Drupal).
   */
  function gatherHookDocumentationFiles() {
    // Get the hooks directory.
    $mb_factory = module_builder_get_factory();
    $directory = $mb_factory->environment->hooks_directory;

    // Get Drupal root folder as a file path.
    // DRUPAL_ROOT is defined both by Drupal and Drush.
    // @see _drush_bootstrap_drupal_root(), index.php.
    $drupal_root = DRUPAL_ROOT;

    $system_listing = $this->drupalSystemListing('/\.api\.php$/', 'modules', 'filename');
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
    // Get data by invoking our hook.
    $data = $this->invokeInfoHook();

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
   * Invoke hook_module_builder_info().
   */
  function invokeInfoHook() {
    $mb_factory = module_builder_get_factory();
    $major_version = $mb_factory->environment->major_version;

    // TODO: just get ours if no bootstrap?
    $mask = '/\.module_builder.inc$/';

    // Based on change record https://www.drupal.org/node/2198695
    // TODO: use drupalSystemListing().
    $mb_files = array();
    foreach (\Drupal::moduleHandler()->getModuleList() as $name => $module) {
      $mb_files += file_scan_directory($module->getPath(), $mask);
    }

    $module_data = array();

    foreach ($mb_files as $file) {
      // Our system listing wrapper ensured that there is a uri property on all versions.
      include_once($file->uri);
      // Use a property of the (badly-documented!) $file object that is common to both D6 and D7.
      $module = str_replace('.module_builder', '', $file->name);
      // Note that bad data got back from the hook breaks things.
      if ($result = module_invoke($module, 'module_builder_info', $major_version)) {
        $module_data = array_merge($module_data, $result);
      }
    }

    //print_r($module_data);

    // If we are running as Drush command, we're not an installed module.
    if (!\Drupal::moduleHandler()->moduleExists('module_builder')) {
      include_once(dirname(__FILE__) . '/../module_builder.module_builder.inc');
      $result = module_builder_module_builder_info($major_version);
      $data = array_merge($module_data, $result);
    }
    else {
      $data = $module_data;
      // Yeah we switch names so the merging above isn't affected by an empty array.
      // Gah PHP. Am probably doin it wrong.
    }

    //drush_print_r($data);
    return $data;
  }

  /**
   * Replacement for drupal_system_listing() for module files.
   *
   * Based on notes in change record at https://www.drupal.org/node/2198695.
   *
   * For spec see https://api.drupal.org/api/drupal/includes!common.inc/function/drupal_system_listing/7
   */
  function drupalSystemListing($mask, $directory, $key = 'name') {
    $files = array();
    foreach (\Drupal::moduleHandler()->getModuleList() as $name => $module) {
      $files += file_scan_directory($module->getPath(), $mask, array('key' => $key));
    }
    return $files;
  }

  /**
   * Get info about hooks from Drupal.
   *
   * This invokes hook_hook_info().
   */
  function getHookInfo($file_data) {
    // Note that the 'module' key is flaky: some modules use a different name
    // for their api.php file.
    $module = $file_data['module'];
    $hook_info = array();
    if (\Drupal::moduleHandler()->implementsHook($module, 'hook_info')) {
      $hook_info = \Drupal::moduleHandler()->invoke($module, 'hook_info');
    }

    return $hook_info;
  }

}
