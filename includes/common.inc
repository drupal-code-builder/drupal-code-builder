<?php

/**
 * @file common.inc
 * Stuff needed both by module and drush command.
 *
 * IMPORTANT: This file should be identical across all versions of Drupal
 * since Drush uses it.
 *
 */

/**
 * Custom exception class.
 */
class ModuleBuilderException extends Exception {
  // Flag set to TRUE if hook data needs downloading (and the folders are ok).
  // This allows us to recover gracefully.
  public $needs_hooks_download;
}
