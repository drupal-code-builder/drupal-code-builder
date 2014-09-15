<?php

/**
 * @file common_version.inc
 *   Stuff needed both by module and drush command.
 *   Functions that need to differ for versions of Drupal.
 *   This file is the default fallback, and covers Drupal 5 and 6.
 */

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
