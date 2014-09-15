<?php

/**
 * @file common_version_8.inc
 *   Stuff needed both by module and drush command.
 *   Functions that need to differ for versions of Drupal.
 */

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
