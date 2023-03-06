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
 * Drupal 9 and 10, then its wrapper does not need to be present in the Drupal 9
 * helper class.
 * @} End of "defgroup drupal_code_builder_environment_version_helpers".
 */

/**
 * Environment helper for Drupal 9.
 */
class VersionHelper9 extends VersionHelper10 {

  protected $major_version = 9;

  /**
   * Get the path to a Drupal extension, e.g. a module or theme
   */
  function getExtensionPath($type, $name) {
    // Check whether the extension exists, as D9 will throw an error when
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
