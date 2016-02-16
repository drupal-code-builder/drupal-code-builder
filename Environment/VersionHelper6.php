<?php

/**
 * @file
 * Contains ModuleBuilder\Environment\VersionHelper6.
 */

namespace ModuleBuilder\Environment;

/**
 * Environment helper for Drupal 6.
 *
 * @ingroup module_builder_environment_version_helpers
 */
class VersionHelper6 extends VersionHelper7 {

  protected $major_version = 6;

  /**
   * Transforms a path into a path within the site files folder, if needed.
   *
   * Eg, turns 'foo' into 'sites/default/foo'.
   * Absolute paths are unchanged.
   */
  function directoryPath(&$directory) {
    if (substr($directory, 0, 1) != '/') {
      // Relative, and so assumed to be in Drupal's files folder: prepend this to
      // the given directory.
      // sanity check. need to verify /files exists before we do anything. see http://drupal.org/node/367138
      $files = file_create_path();
      file_check_directory($files, FILE_CREATE_DIRECTORY);
      $directory = file_create_path($directory);
    }
  }

  /**
   * Check that the directory exists and is writable, creating it if needed.
   *
   * @throws
   *  Exception
   */
  function prepareDirectory($directory) {
    // Because we may have an absolute path whose base folders are not writable
    // we can't use the standard recursive D6 pattern.
    $pieces = explode('/', $directory);

    // Work up through the folder's parentage until we find a directory that exists.
    // (Or in other words, backwards in the array of pieces.)
    $length = count($pieces);
    for ($i = 0; $i < $length; $i++) {
      //print $pieces[$length - $i];
      $slice = array_slice($pieces, 0, $length - $i);
      $path_slice = implode('/', $slice);
      if (file_exists($path_slice)) {
        $status = file_check_directory($path_slice, FILE_CREATE_DIRECTORY);
        break;
      }
    }

    // If we go right the way along to the base and still can't create a directory...
    if ($i == $length) {
      throw new \Exception(strtr("The directory !path cannot be created or is not writable", array(
        '!path' => htmlspecialchars($path_slice, ENT_QUOTES, 'UTF-8'),
      )));
    }
    // print "status: $status for $path_slice - i: $i\n";

    // Now work back down (or in other words, along the array of pieces).
    for ($j = $length - $i; $j < $length; $j++) {
      $slice[] = $pieces[$j];
      $path_slice = implode('/', $slice);
      //print "$path_slice\n";
      $status = file_check_directory($path_slice, FILE_CREATE_DIRECTORY);
    }

    if (!$status) {
      throw new \Exception("The hooks directory cannot be created or is not writable.");
    }
  }

  /**
   * A version-independent wrapper for drupal_system_listing().
   */
  function systemListing($mask, $directory, $key = 'name', $min_depth = 1) {
    $files = drupal_system_listing($mask, $directory, $key, $min_depth);

    // This one is actually only for Drupal 6.
    // The file object is:
    //    D6         D7         what it actually is
    //  - filename | uri      | full path and name
    //  - basename | filename | name with the extension
    //  - name     | name     | name without the extension
    // So we copy filename to uri, and then the caller can handle the returned
    // array as if it were Drupal 7 style.
    foreach ($files as $file) {
      $file->uri = $file->filename;
    }

    return $files;
  }

  /**
   * Invoke hook_module_builder_info().
   *
   * @return
   *  Data gathered from the hook implementations.
   */
  public function invokeInfoHook() {
    $major_version = $this->major_version;

    // TODO: just get ours if no bootstrap?
    $mb_files = $this->systemListing('/\.module_builder.inc$/', 'modules');
    //print_r($mb_files);

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

    return $module_data;
  }

}
