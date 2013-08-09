<?php

/**
 * @file
 * Definition of ModuleBuider\Task\AnalyzeModule.
 */

namespace ModuleBuider\Task;

/**
 * Task handler for analyzing an existing module.
 */
class AnalyzeModule extends Base {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'hook_data';

  /**
   * Helper function to get all the code files for a given module
   *
   * TODO: does drush have this?
   *
   * (Replaces module_builder_get_module_files().)
   *
   * @param $module_root_name
   *  The root name of a module, eg 'node', 'taxonomy'.
   *
   * @return
   *  A flat array of filenames.
   */
  function getFiles($module_root_name) {
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
   * (Replaces module_builder_get_functions().)
   *
   * @param $file
   *  A complete filename from the Drupal root, eg 'modules/user/user.module'.
   */
  function getFileFunctions($file) {
    $code = file_get_contents($file);
    //drush_print($code);

    $matches = array();
    $pattern = "/^function (\w+)/m";
    preg_match_all($pattern, $code, $matches);

    return $matches[1];
  }

}
