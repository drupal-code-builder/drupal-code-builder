<?php

/**
 * @file common_version.inc
 *   Stuff needed both by module and drush command.
 *   Functions that need to differ for versions of Drupal.
 *   This file is the default fallback, and covers Drupal 5 and 6.
 */

/**
 * Run through Drupal's directory creation and checking stuff, 
 * adding a version subfolder for common directories.
 *
 * @param $directory
 *  A directory path. Should be either absolute or relative; the latter will
 *  be taken to mean it's inside the Drupal /files directory.
 * @param $common
 *  Boolean to indicate whether this directory is common across several 
 *  Drupal installations. If set, the actual directory used will be a
 *  subdirectory with a version number: eg path/to/dir/6
 */
function module_builder_create_directory(&$directory, $common = FALSE) {
  if (substr($directory, 0, 1) == '/') {
    // absolute path
    //print 'starts with /'; 
  }
  else {
    // Relative, and so assumed to be in Drupal's files folder.
    // sanity check. need to verify /files exists before we do anything. see http://drupal.org/node/367138
    $files = file_create_path();
    file_check_directory($files, FILE_CREATE_DIRECTORY);    
    $directory = file_create_path($directory);    
  }
  
  // Note that recursive stuff won't work here!
  // You really should make sure the dir exists yourself.
  file_check_directory($directory, FILE_CREATE_DIRECTORY);
  
  // If the directory is common, append the version number subdir.
  if ($common) {
    // $directory has no trailing / at this point: file_check_directory removes it. 
    $directory = $directory . '/' . _module_builder_drupal_major_version();  
    file_check_directory($directory, FILE_CREATE_DIRECTORY);
  }
}

/**
 * A version-independent wrapper for drupal_system_listing().
 */
function module_builder_system_listing($mask, $directory, $key = 'name', $min_depth = 1) {
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
