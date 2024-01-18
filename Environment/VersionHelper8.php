<?php

namespace DrupalCodeBuilder\Environment;

/**
 * @defgroup drupal_code_builder_environment_version_helpers Environment version helpers
 * @{
 * Wrapper objects for Drupal APIs that change between Drupal major versions.
 *
 * These allow the environment classes to work orthogonally across different
 * environments (Drush, Drupal UI) and different core versions.
 *
 * Each major version of Drupal core needs a version helper class, set up with
 * \DrupalCodeBuilder\Environment\EnvironmentInterface\setCoreVersionNumber().
 * No direct calls should be made to the helper, rather, the environment base
 * class should provide a wrapper.
 *
 * Version helper classes inherit in a cascade, with older versions inheriting
 * from newer. This means that if, say, an API function does not change between
 * Drupal 6 and 7, then its wrapper does not need to be present in the Drupal 6
 * helper class.
 * @} End of "defgroup drupal_code_builder_environment_version_helpers".
 */

/**
 * Environment helper for Drupal 8.
 */
class VersionHelper8 {

  protected $major_version = 8;

  /**
   * {@inheritdoc}
   */
  public function getCoreMajorVersion() {
    return $this->major_version;
  }

  /**
   * Transforms a path into a path within the site files folder, if needed.
   *
   * Eg, turns 'foo' into 'public://foo'.
   * Absolute paths are unchanged.
   */
  function directoryPath(&$directory) {
    if (!str_starts_with($directory, '/')) {
      // Relative, and so assumed to be in Drupal's files folder: prepend this to
      // the given directory.
      $directory = 'public://' . $directory;
    }
  }

  /**
   * Check that the directory exists and is writable, creating it if needed.
   *
   * @throws
   *  Exception
   */
  function prepareDirectory($directory) {
    $status = \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);
    if (!$status) {
      throw new \Exception();
    }
  }

  /**
   * A version-independent wrapper for drupal_system_listing().
   *
   * Based on notes in change record at https://www.drupal.org/node/2198695.
   */
  function systemListing($mask, $directory, $key = 'name', $min_depth = 1) {
    $files = [];
    foreach (\Drupal::moduleHandler()->getModuleList() as $name => $module) {
      $files += \Drupal::service('file_system')->scanDirectory($module->getPath(), $mask, ['key' => $key]);
    }
    return $files;
  }

  /**
   * Invoke hook_module_builder_info().
   */
  function invokeInfoHook() {
    $major_version = $this->major_version;

    // TODO: just get ours if no bootstrap?
    $mask = '/\.module_builder.inc$/';
    $mb_files = $this->systemListing($mask, 'modules');

    $module_data = [];

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

    return $module_data;
  }

  /**
   * Get a user preference setting.
   */
  public function getSetting($name, $default = NULL) {
    $setting_name_lookup = [
      'data_directory' => 'data_directory',
      // Others aren't supported on D8 (yet?).
    ];

    if (isset($setting_name_lookup[$name])) {
      $config = \Drupal::config('module_builder.settings');
      $value = $config->get($setting_name_lookup[$name]);
      return $value ?? $default;
    }
    else {
      return $default;
    }
  }

  /**
   * Get the path to a Drupal extension, e.g. a module or theme
   */
  function getExtensionPath($type, $name) {
    // Check whether the extension exists, as D8 will throw an error when
    // calling drupal_get_path() for a non-existent extension.
    switch ($type) {
      case 'module':
        if (!\Drupal::moduleHandler()->moduleExists($name)) {
          return;
        }
        break;
      case 'theme':
        if (!\Drupal::service('theme_handler')->themeExists($name)) {
          return;
        }
        break;
      case 'profile':
        // TODO.
        break;
    }

    return drupal_get_path($type, $name);
  }

}
