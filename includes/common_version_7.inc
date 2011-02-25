<?php

/**
 * @file common_version_7.inc
 *   Stuff needed both by module and drush command.
 *   Functions that need to differ for versions of Drupal.
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
    // Relative, and so assumed to be in Drupal's files folder: prepend this to
    // the given directory.
    $directory = 'public://' . $directory;
  }

  // If the directory is common, append the version number subdir.
  if ($common) {
    // There may or may not be a trailing slash: trim to make sure.
    $directory = rtrim($directory, '/');
    $directory = $directory . '/' . _module_builder_drupal_major_version();  
  }

  $status = file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
  // @todo: check $status for errors
}

/**
 * A version-independent wrapper for drupal_system_listing().
 */
function module_builder_system_listing($mask, $directory, $key = 'name', $min_depth = 1) {
  $mask = "/$mask/";
  return drupal_system_listing($mask, $directory, $key, $min_depth);
}
