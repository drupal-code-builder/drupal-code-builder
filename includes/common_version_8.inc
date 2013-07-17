<?php

/**
 * @file common_version_8.inc
 *   Stuff needed both by module and drush command.
 *   Functions that need to differ for versions of Drupal.
 */

/**
 * Transforms a path into a path within the site files folder, if needed.
 *
 * Eg, turns 'foo' into 'public://foo'.
 * Absolute paths are unchanged.
 */
function module_builder_directory_path(&$directory) {
  if (substr($directory, 0, 1) != '/') {
    // Relative, and so assumed to be in Drupal's files folder: prepend this to
    // the given directory.
    $directory = 'public://' . $directory;
  }
}

/**
 * Check that the directory exists and is writable, creating it if needed.
 *
 * @throw
 *  ModuleBuilderException
 */
function module_builder_prepare_directory($directory) {
  $status = file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
  if (!$status) {
    throw new ModuleBuilderException("The hooks directory cannot be created or is not writable.");
  }
}

/**
 * A version-independent wrapper for drupal_system_listing().
 */
function module_builder_system_listing($mask, $directory, $key = 'name', $min_depth = 1) {
  $mask = "/$mask/";
  return drupal_system_listing($mask, $directory, $key, $min_depth);
}
