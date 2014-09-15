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
 * Helper function to invoke hook_module_builder_info() in all modules.
 *
 * The tricky part is that we want to include ourselves, but module_builder
 * might not be installed (or even present) in Drupal if we are on Drush.
 *
 * @deprecated
 */
function _module_builder_invoke_hook() {
  $mb_factory = module_builder_get_factory();
  return $mb_factory->environment->invokeInfoHook();
}

/**
 * Custom exception class.
 */
class ModuleBuilderException extends Exception {
  // Flag set to TRUE if hook data needs downloading (and the folders are ok).
  // This allows us to recover gracefully.
  public $needs_hooks_download;
}
