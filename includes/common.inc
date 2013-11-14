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
 */
function _module_builder_invoke_hook() {
  $mb_factory = module_builder_get_factory();
  $mb_factory->environment->loadInclude('common_version');
  $major_version = $mb_factory->environment->major_version;

  // TODO: just get ours if no bootstrap?
  $mb_files = module_builder_system_listing('\.module_builder.inc$', 'modules');
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

  //print_r($module_data);

  // If we are running as Drush command, we're not an installed module.
  if (!module_exists('module_builder')) {
    include_once(dirname(__FILE__) . '/../module_builder.module_builder.inc');
    $result = module_builder_module_builder_info($major_version);
    $data = array_merge($module_data, $result);
  }
  else {
    $data = $module_data;
    // Yeah we switch names so the merging above isn't affected by an empty array.
    // Gah PHP. Am probably doin it wrong.
  }

  //drush_print_r($data);
  return $data;
}

/**
 * Custom exception class.
 */
class ModuleBuilderException extends Exception {
  // Flag set to TRUE if hook data needs downloading (and the folders are ok).
  // This allows us to recover gracefully.
  public $needs_hooks_download;
}
