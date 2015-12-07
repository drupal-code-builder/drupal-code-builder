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
   * Set the hooks directory.
   */
  function setHooksDirectory() {
    // Set the module folder based on variable.
    $directory = $this->getSetting('module_builder_hooks_directory', 'hooks');

    // Run it through version-specific stuff.
    // This basically prepends 'public://' or 'sites/default/files/'.
    $this->version_helper->directoryPath($directory);

    $this->hooks_directory = $directory;
  }

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
