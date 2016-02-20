<?php

/**
 * @file
 * Contains ModuleBuilder\Environment\Drush.
 */

namespace ModuleBuilder\Environment;

/**
 * Environment class for use as a Drush plugin.
 */
class Drush extends BaseEnvironment {

  /**
   * {@inheritdoc}
   */
  protected function getHooksDirectorySetting() {
    // Get the hooks directory.
    $directory = $this->getHooksDirectorySettingHelper();

    // Run it through version-specific stuff.
    // This basically prepends 'public://' or 'sites/default/files/'.
    $this->version_helper->directoryPath($directory);

    $this->hooks_directory = $directory;
  }

  /**
   * Get the hooks directory.
   *
   * On Drush, this can come from several places, in the following order of
   * preference:
   *  - The Drush --data option. This allows use of a central store of hook data
   *    that needs only be downloaded once for all Drupal sites. Subdirectories
   *    are made for each major version.
   *  - The Module Builder UI's variable. This will only be set if module
   *    builder has been installed as a Drupal module on the current site.
   */
  private function getHooksDirectorySettingHelper() {
    // Set the module folder based on variable.
    // First try the drush 'data' option.
    if (drush_get_option('data')) {
      $directory = drush_get_option('data');
      if ($directory) {
        // In pure Drush, the hooks folder contains subfolder for hooks for
        // each major version of Drupal.
        if (substr($directory, -1, 1) != '/') {
          $directory .= '/';
        }
        $directory .= $this->getCoreMajorVersion();
        return $directory;
      }
    }
    // Second, use the config setting, in case we're in drush, but the site has
    // Module Builder running as a module.
    $directory = $this->getSetting('data_directory', 'hooks');
    return $directory;
  }

  /**
   * Output debug data.
   */
  function debug($data, $message = '') {
    drush_print_r("== $message:");
    drush_print_r($data);
  }

  /**
   * Get a path to a module builder file or folder.
   */
  function getPath($subpath) {
    // On Drush we just have to jump through hoops.
    $mb_path = dirname(__FILE__) . '/..';

    $path = $mb_path . '/' . $subpath;

    return $path;
  }

}
