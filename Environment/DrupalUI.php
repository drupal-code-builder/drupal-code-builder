<?php

/**
 * @file
 * Contains ModuleBuilder\Environment\DrupalUI.
 */

namespace ModuleBuilder\Environment;

/**
 * Environment class for Drupal UI.
 *
 * TODO: retire this; it's just for transition?
 */
class DrupalUI extends BaseEnvironment {

  /**
   * Get a path to a module builder file or folder.
   */
  function getPath($subpath) {
    $path = drupal_get_path('module', 'module_builder');
    $path = $path . '/' . $subpath;
    return $path;
  }

  /**
   * Output debug data.
   */
  function debug($data, $message = '') {
    if (module_exists('devel')) {
      dpm($data, $message);
    }
  }

}
