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
 * Get a path to a resource that is safe to use either on Drupal or Drush.
 *
 * @param $subpath
 *    The subpath inside the module_builder folder. Eg, 'templates'.
 */
function module_builder_get_path($subpath) {
  if (MODULE_BUILDER_ENV == 'drupal') {
    $mb_path = drupal_get_path('module', 'module_builder');
  }

  if (!isset($mb_path)) {
    $mb_path = dirname(__FILE__) . '/..';
  }

  $path = $mb_path . '/' . $subpath;

  //print "path: >>$path<<";

  return $path;
}

/**
 * Include a version-specific file whether we're on drush or drupal.
 * That is, we first try to include a file called NAME_X.inc where X is a
 * Drupal major version number before falling back to NAME.inc.
 *
 * Files are included from the 'includes' folder inside module_builder.
 *
 * On Drush, this is a wrapper for drush_include().
 * On Drupal, this just goes straight for the current version.
 *
 * @param $name
 *  The filename, eg 'update'.
 * @param $extension
 *  The file extension.
 */
function module_builder_include($name, $extension = 'inc') {
  $path = module_builder_get_path('includes');
  if (MODULE_BUILDER_ENV == 'drush') {
    // In Drush.
    // the NULL means drush_include will try to find the version.
    drush_include($path, $name, NULL, $extension);
  }
  else {
    // In Drupal GUI.
    // Try the versioned file first.
    $file = sprintf("%s/%s_%s.%s", $path, $name, _module_builder_drupal_major_version(), $extension);
    //dsm($file);
    if (file_exists($file)) {
      require_once($file);
      return;
    }
    // Fall back to the regular file.
    $file = sprintf("%s/%s.%s", $path, $name, $extension);
    require_once($file);
  }
}

/**
 * Returns the Drupal major version number (5, 6, 7 ...)
 *
 * Helper function for module_builder_include. Cribbed and hacked from drush.
 */
function _module_builder_drupal_major_version() {
  list($major_version) = explode('.', VERSION);
  return $major_version;
}

/**
 * Helper function to get all the code files for a given module
 * TODO: does drush have this?
 *
 * @param $module_root_name
 *  The root name of a module, eg 'node', 'taxonomy'.
 *
 * @return
 *  A flat array of filenames.
 */
function module_builder_get_module_files($module_root_name) {
  $filepath = drupal_get_path('module', $module_root_name);

  //$old_dir = getcwd();
  //chdir($filepath);
  $files = scandir($filepath);

  foreach ($files as $filename) {
    $ext = substr(strrchr($filename, '.'), 1);
    if (in_array($ext, array('module', 'install', 'inc'))) {
      $module_files[] = $filepath . '/' . $filename;
    }
  }

  return $module_files;
}

/**
 * Helper function to get all function names from a file.
 *
 * @param $file
 *  A complete filename from the Drupal root, eg 'modules/user/user.module'.
 */
function module_builder_get_functions($file) {
  $code = file_get_contents($file);
  //drush_print($code);

  $matches = array();
  $pattern = "/^function (\w+)/m";
  preg_match_all($pattern, $code, $matches);

  return $matches[1];
}


/**
 * Helper function to invoke hook_module_builder_info() in all modules.
 *
 * The tricky part is that we want to include ourselves, but module_builder
 * might not be installed (or even present) in Drupal if we are on Drush.
 */
function _module_builder_invoke_hook() {
  // TODO: just get ours if no bootstrap?
  module_builder_include('common_version');
  $mb_files = module_builder_system_listing('\.module_builder.inc$', 'modules');
  //print_r($mb_files);

  $module_data = array();

  foreach ($mb_files as $file) {
    // Our system listing wrapper ensured that there is a uri property on all versions.
    include_once($file->uri);
    // Use a property of the (badly-documented!) $file object that is common to both D6 and D7.
    $module = str_replace('.module_builder', '', $file->name);
    // Note that bad data got back from the hook breaks things.
    if ($result = module_invoke($module, 'module_builder_info')) {
      $module_data = array_merge($module_data, $result);
    }
  }

  //print_r($module_data);

  // If we are running as Drush command, we're not an installed module.
  if (!module_exists('module_builder')) {
    include_once(dirname(__FILE__) . '/../module_builder.module_builder.inc');
    $result = module_builder_module_builder_info();
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
