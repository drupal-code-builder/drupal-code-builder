<?php

/**
 * @file
 * Contains ModuleBuilder\Environment\BaseEnvironment.
 *
 * The environment system provides an abstraction layer between Module Builder
 * and its current environment, e.g., whether we are running as a Drush plugin,
 * a Drupal module, or being loaded as a library, and what major version of
 * Drupal core we are running on. The environment handler takes care of things
 * such as:
 *  - how to output debug data
 *  - how to get the Drupal core version
 *  - how to load an include file with a version suffix
 *  - how to find the hooks data directory.
 * The classes for the execution environment (Drush, Drupal, library) are
 * supported by helper classes for Drupal core version, thus allowing the
 * execution environment to be orthogonal to the major version. All methods on
 * the helper core version object should be access via a wrapper on the main
 * environment class.
 */

namespace ModuleBuilder\Environment;

/**
 * Base class for environments.
 */
abstract class BaseEnvironment implements EnvironmentInterface {

  /**
   * Whether to skip the sanity tests.
   *
   * @see skipSanityCheck()
   */
  protected $skipSanity = FALSE;

  /**
   * The path to the hooks directory.
   *
   * Depending on our environment this is either relative to Drupal root or
   * absolute, but in either case it is in a format that other environment
   * methods can use.
   *
   * Initially this only represents a user setting, and is not verified
   * as an existing, writable directory unless the Task's sanity level has
   * requested it.
   */
  public $hooks_directory;

  /**
   * The major Drupal core version.
   *
   * @see getCoreMajorVersion()
   */
  protected $major_version;

  /**
   * A helper object for version-specific code.
   *
   * This allows code specific to different major version of Drupal to be
   * orthogonal to the environment, without external systems having to deal
   * with it.
   */
  protected $version_helper;

  /**
   * Constructor.
   */
  function __construct() {
    // Set the major version.
    $this->detectMajorVersion();

    // Set up the helper for version-specific code.
    $this->initVersionHelper();

    // Set the hooks directory.
    $this->setHooksDirectory();
  }

  /**
   * Set the hooks directory.
   */
  abstract protected function setHooksDirectory();

  /**
   * @inheritdoc
   */
  public function getHooksDirectory() {
    return $this->hooks_directory;
  }

  /**
   * @inheritdoc
   */
  public function verifyEnvironment($sanity_level) {
    // Allow the environment to request skipping the sanity checks.
    if ($this->skipSanity) {
      return;
    }

    // Sanity level 'none': nothing to do.
    if ($sanity_level == 'none') {
      return;
    }

    // Sanity level 'hook_directory':
    $this->version_helper->prepareDirectory($this->hooks_directory);

    // This is as far as we need to go for the hooks_directory level.
    if ($sanity_level == 'hook_directory') {
      return;
    }

    // Sanity level 'hook_data':
    $hooks_processed = $this->hooks_directory . "/hooks_processed.php";
    if (!file_exists($hooks_processed)) {
      $e = new \ModuleBuilder\Exception("No hook definitions found. You need to download hook definitions before using this module.");
      $e->needs_hooks_download = TRUE;
      throw $e;
    }

    // This is as far as we need to go for the hook_data level.
    if ($sanity_level == 'hook_data') {
      return;
    }

    // There are no further sanity levels!
  }

  /**
   * @inheritdoc
   */
  public function skipSanityCheck($setting) {
    $this->skipSanity = $setting;
  }

  /**
   * @inheritdoc
   */
  public function getCoreMajorVersion() {
    return $this->major_version;
  }

  /**
   * Output debug data.
   */
  abstract function debug($data, $message = '');

  /**
   * Detect the major Drupal core version and set the property for it.
   *
   * Helper for __construct().
   */
  protected function detectMajorVersion() {
    // ARGH D8 is different and at this point we can't specialize per-version,
    // since we're trying to GET the version!
    if (defined('VERSION')) {
      $version = VERSION;
    }
    else {
      $version = \Drupal::VERSION;
    }

    list($major_version) = explode('.', $version);

    $this->major_version = $major_version;
  }

  /**
   * Initialize the version helper object.
   *
   * Helper for __construct().
   */
  protected function initVersionHelper() {
    $helper_class_name = '\ModuleBuilder\Environment\VersionHelper' . $this->major_version;

    $this->version_helper = new $helper_class_name($this);
  }

  /**
   * @inheritdoc
   */
  public function systemListing($mask, $directory, $key = 'name', $min_depth = 1) {
    return $this->version_helper->systemListing($mask, $directory, $key, $min_depth);
  }

  /**
   * @inheritdoc
   */
  public function invokeInfoHook() {
    // The tricky part is that we want to include ourselves, but module_builder
    // might not be installed (or even present) in Drupal if we are on Drush.
    return $this->version_helper->invokeInfoHook();
  }

  /**
   * @inheritdoc
   */
  public function getSetting($name, $default = NULL) {
    return $this->version_helper->getSetting($name, $default);
  }

}
