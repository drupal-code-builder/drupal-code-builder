<?php

/**
 * @file
 * Module builder file downloading.
 * 
 * Fetches files from locations specified by hook_module_builder_info 
 * implementations (including our own).
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
 *  - destination: the module code file where the hooks from this hook data file
 *    should be saved by code generation
 *  - hook_destinations: an array of destinations for specific hooks. Each key
 *    is a full hook name, each value is a destination. This is converted from
 *    the data structure hook_module_builder_info() returns.
 *  - group: the group this file's hooks should be shown in the Drupal UI.
 * Example:
 *  [system.core.php] => array(
 *    [path]        => /Users/you/data/drupal_hooks/6/system.core.php
 *    [url]         => http://cvs.drupal.org/viewvc.py/drupal/contributions/docs/developer/hooks/core.php?view=co&pathrev=DRUPAL-6--1
 *    [destination] => %module.module
 *    [group]       => core
 */

/**
 * Updates hook documentation files.
 *
 * This function should be called after all settings have been checked.
 * It ensures hook documentation files are available (on Drupal 5 and 6 by
 * downloading them).
 *
 * After calling this function, you probably want to pass the returned list
 * of files to module_builder_process_hook_data().
 * Though really, instead of this function you probably want module_builder_update_data().
 * Just saying.
 *
 * @return
 *  Array of hook files suitable for passing to module_builder_process_hook_data().
 *  See file documentation for details.
 */
function module_builder_update_documentation() {
  $directory = _module_builder_get_hooks_directory();
  //print_r($directory);

  // Fetch data about the files we need to download.
  $hook_files = _module_builder_get_hook_file_urls();
  //print_r($hook_files);
  
  // For testing only: skip downloading, just process.
  /*
  module_builder_process_hook_data($hook_files);
  return $hook_files;
  */
  
  // Retrieve each file and store it in the hooks directory, overwriting what's currently there
  foreach ($hook_files as $file_name => $data) {
    $file_contents = drupal_http_request($data['url']);
    
    _module_builder_drush_print("writing $directory/$file_name", 2);
    file_put_contents("$directory/$file_name", $destination . $file_contents->data);
  }
  
  // inform that hook documentation has been downloaded.
  drupal_set_message(t("Module Builder has just downloaded hook documentation to your %dir directory from CVS. This documentation contains detailed descriptions and usage examples of each of Drupal's hooks. Please view the files for more information, or view them online at the <a href=\"!api\">Drupal API documentation</a> site.", array('%dir' => 'files/'. variable_get('module_builder_hooks_directory', 'hooks'), '!api' => url('http://api.drupal.org/'))));
  
  return $hook_files;
}

/**
 * Get list of hook file URLS from any modules that declare them.
 *
 * @return
 *   An array of data about the files to download, keyed by (safe) filename:
    [system.core.php] => Array
      [path] => the full path this file should be saved to
      [url] => URL
      [destination] => %module.module
      [group] => core
 */
function _module_builder_get_hook_file_urls() {
  // Get data by invoking our hook.
  $data = _module_builder_invoke_hook();
    
  foreach ($data as $module => $module_data) {
    $branch = $module_data['branch'];
    foreach ($module_data['hook_files'] as $hook_file => $destination) {
      $url = str_replace(
        array('%file', '%branch'),
        array($hook_file, $branch),
        $module_data['url']
      );
      // Create our own safe filename with module prefix.
      $hook_file_safe_name = "$module.$hook_file";
      $directory = _module_builder_get_hooks_directory();      
      $urls[$hook_file_safe_name]['path'] = $directory . '/' . $hook_file_safe_name;  
      $urls[$hook_file_safe_name]['url'] = $url;
      $urls[$hook_file_safe_name]['destination'] = $destination;
      if (isset($module_data['hook_destinations'])) {
        $urls[$hook_file_safe_name]['hook_destinations'] = array();
        foreach ($module_data['hook_destinations'] as $destination => $hooks) {
          $urls[$hook_file_safe_name]['hook_destinations'] += array_fill_keys($hooks, $destination);
        }
      }
      if ($module_data['group'] == '#filenames') {
        $urls[$hook_file_safe_name]['group'] = str_replace('.php', '', $hook_file);
      }
      else {
        $urls[$hook_file_safe_name]['group'] = $module_data['group'];
      }
    }
  }
  
  //print_r($urls);
  
  return $urls;
}

/**
 * Helper function for building URLs.
 *
 * Get the branch tag, given the minor revision.
 * Gets the major revision from the current Drupal install.
 */
function _module_builder_get_cvs_branch($minor) {
  list($major, ) = explode('.', VERSION); //
  $cvs_version = "DRUPAL-$major--$minor";
  return $cvs_version;
}
