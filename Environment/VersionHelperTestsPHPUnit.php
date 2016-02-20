<?php

/**
 * @file
 * Contains ModuleBuilder\BaseEnvironment\VersionHelperTests.
 */

namespace ModuleBuilder\Environment;

/**
 * Environment helper for testing with PHPUnit.
 */
class VersionHelperTestsPHPUnit {

  protected $major_version = NULL;

  protected $environment;

  /**
   * Set the core version number.
   *
   * Allow this to be set after construction so that test classes can change it.
   *
   * @param $version
   *  A core major version number,
   */
  public function setFakeCoreMajorVersion($version) {
    $this->major_version = $version;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoreMajorVersion() {
    return $this->major_version;
  }

  /**
   * Transforms a path into a path within the site files folder, if needed.
   */
  function directoryPath(&$directory) {
    // Not needed: Test environment classes bypass calls to this.
  }

  /**
   * Check that the directory exists and is writable, creating it if needed.
   *
   * @throws
   *  Exception
   */
  function prepareDirectory($directory) {
    // Does nothing.
  }

  /**
   * A version-independent wrapper for drupal_system_listing().
   */
  function systemListing($mask, $directory, $key = 'name', $min_depth = 1) {
    return array();
  }

  /**
   * Invoke hook_module_builder_info().
   */
  function invokeInfoHook() {
    return array();
  }

  /**
   * Get a user preference setting.
   */
  public function getSetting($name, $default = NULL) {
    // Just return the default.
    return $default;
  }


  /**
   * Get the path to a Drupal extension, e.g. a module or theme
   */
  function getExtensionPath($type, $name) {
    // There is no Drupal, so no extensions.
    return NULL;
  }

}
