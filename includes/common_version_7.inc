<?php

/**
 * @file common_version_7.inc
 *   Stuff needed both by module and drush command.
 *   Functions that need to differ for versions of Drupal.
 */

/**
 * A version-independent wrapper for drupal_system_listing().
 */
function module_builder_system_listing($mask, $directory, $key = 'name', $min_depth = 1) {
  $mask = "/$mask/";
  return drupal_system_listing($mask, $directory, $key, $min_depth);
}
