<?php

/**
 * @file
 * Module builder: get list of hook files for Drupal 8.
 *
 * These are located in the current Drupal installation and copied to the hooks
 * directory. (Keeping our own copies means that multiple installations of 
 * Drupal that this command is run on will accumulate hook data from different 
 * contrib modules rather than repeatedly clobber other sites' data.)
 *
 * The main function here is module_builder_update_documentation().
 * Other functions (beginning with underscores) are internal to this file.
 * 
 * The data that gets passed around is an array keyed by filename. Filenames
 * thus be unique; if there is a possibility of filename clash these must 
 * be rendered safe, for example by prefixing the module name.
 * The keys to each item are:
 *  - path: the full path to this file
 *  - url: (internal to this file) URL to download this file from.
 *  - original: (probably not used; just here for interest) the full path this file was copied from.
 *  - destination: the module code file where the hooks from this hook data file
 *    should be saved by code generation
 *  - hook_destinations: per-hook overrides to destination
 *  - group: the group this file's hooks should be shown in the Drupal UI.
 *  - module: the module that provided this file. WARNING: this is not entirely
 *    reliable; see module_builder_update_documentation() for details.
 * Example:
 *  [system.core.php] => array(
 *    [path]        => /Users/you/data/drupal_hooks/8/system.api.php
 *    [url]         => (not used on 8)
 *    [original]    => /Users/joachim/Sites/8-drupal/modules/system/system.api.php
 *    [destination] => %module.module
 *    [group]       => core
 *    [module]      => node
 */
 
/**
 * Updates hook documentation files.
 *
 * This function should be called after all settings have been checked.
 * It retrieves a list of api hook documentation files from the current
 * Drupal install. On D7 these are files of the form MODULE.api.php and are 
 * present in the codebase (rather than needing to be downloaded from CVSview
 * as was the case in previous versions of Drupal).
 *
 * Should be called from Collect::collectHooks().
 *
 * @return
 *  Array of hook files suitable for passing to module_builder_process_hook_data().
 *  See file documentation for details.
 */
function module_builder_update_documentation() {
  // Get the hooks directory.
  $mb_factory = module_builder_get_factory();
  $directory = $mb_factory->environment->hooks_directory;
  
  // Get Drupal root folder as a file path.
  // DRUPAL_ROOT is defined both by Drupal and Drush.
  // @see _drush_bootstrap_drupal_root(), index.php.
  $drupal_root = DRUPAL_ROOT;

  $system_listing = drupal_system_listing('/\.api\.php$/', 'modules', 'filename');
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
  _module_builder_get_hook_destinations($hook_files);
  
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
function _module_builder_get_hook_destinations(&$hook_files) {
  // Get data by invoking our hook.
  $data = _module_builder_invoke_hook();
  
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
