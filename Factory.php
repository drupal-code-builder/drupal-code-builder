<?php

/**
 * @file
 * Contains ModuleBuilder\Factory.
 */

namespace ModuleBuilder;

// Include the Composer autoloader for our classes.
// We use this if we're not being used as a Composer library, for example if
// installed as a Drupal library in sites/all/libraries, or running as a Drupal
// module.
// If we're being used as a Composer library, then we must skip this, as
// Composer's autoloader uses 'require' rather than 'require_once' and thus PHP
// would crash due to redeclaration of our classes.
// We use one of our classes as a litmus test: if it can be found already, then
// our autoloading has already been registered by a host application's Composer.
if (!class_exists('\ModuleBuilder\Environment\BaseEnvironment')) {
  require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Static factory class for creating Module Builder task handlers.
 *
 * A Task Handler is an object which provides a public API which can be used by
 * different UIs for performing various module builder tasks.
 *
 * The process for using this factory is:
 *  - Set the environment that represents where you are using Module Builder.
 *  - Get the task handler. This checks the environment to see whether it is
 *    suitably set up for the requested task (eg, whether the hooks directory
 *    exists, whether hook data has been compiled). This throws an exception if
 *    the environment is not in a state the task requires.
 *  - Execute the required method on the task handler.
 *
 * @code
 *  include_once('path/to/Factory.php');
 *  \ModuleBuilder\Factory::setEnvironmentClass('Drush', 8);
 *  $task = \ModuleBuilder\Factory::getTask('ReportHookData');
 * @endcode
 */
class Factory {

  /**
   * The current environment object; subclass of ModuleBuilderEnvironmentBase.
   *
   * @see setEnvironmentClass()
   * @see getEnvironment()
   */
  protected static $environment;

  /**
   * Set the environment using a class name.
   *
   * This is a convenience wrapper around setEnvironment() for when using an
   * environment class native to Module Builder.
   *
   * @param $environment_class
   *  The name of the environment class to set on the factory, relative to the
   *  \ModuleBuilder\Environment namespace (to use a class outside of that
   *  namespace, use setEnvironment()). No checks are run on the environment
   *  at this stage (and therefore the environment handler may be flagged to
   *  skip checks via the returned factory).
   * @param $drupal_core_version
   *  The version of Drupal core that this is being used in. May be a full
   *  version number, e.g. 8.0.1 or 7.1, or just the major version, e.g. 8.
   *
   * @return
   *  The environment object.
   */
  public static function setEnvironmentClass($environment_class, $drupal_core_version) {
    // Create the environment handler and set it on the factory.
    $environment_class = '\ModuleBuilder\Environment\\' . $environment_class;
    $environment = new $environment_class;
    $version_helper = self::createVersionHelper($drupal_core_version);

    self::setEnvironment($environment, $version_helper);

    return self::$environment;
  }

  /**
   * Set the environment object.
   *
   * @param \ModuleBuilder\Environment\EnvironmentInterface $environment
   *  An environment object to set.
   * @param $version_helper
   *  A version helper object.
   */
  public static function setEnvironment(\ModuleBuilder\Environment\EnvironmentInterface $environment, $version_helper) {
    $environment->setVersionHelper($version_helper);
    $environment->initEnvironment();

    self::$environment = $environment;
  }

  /**
   * Create an environment version helper object.
   *
   * @param $drupal_core_version
   *  The version of Drupal core that this is being used in. May be a full
   *  version number, e.g. 8.0.1 or 7.1, or just the major version, e.g. 8.
   *
   * @return
   *  The version helper object.
   */
  protected static function createVersionHelper($drupal_core_version) {
    // Get the major version from the core version number.
    list($major_version) = explode('.', $drupal_core_version);

    $helper_class_name = '\ModuleBuilder\Environment\VersionHelper' . $major_version;

    $version_helper = new $helper_class_name();

    return $version_helper;
  }

  /**
   * Get the environment object.
   *
   * @return
   *  The environment object.
   */
  public static function getEnvironment() {
    if (!isset(self::$environment)) {
      throw new \Exception("Environment not set.");
    }
    return self::$environment;
  }

  /**
   * Get a new task handler.
   *
   * This creates a new task handler object as requested, sets the environment
   * handler on it, then checks the environment for the environment level the
   * task declares that it needs.
   *
   * @param $task_type
   *  The type of task. This should the the name of a class in the
   *  ModuleBuilder\Task namespace, without the Drupal core version suffix.
   *  May be one of:
   *    - 'Collect': Collect and process data on available hooks.
   *    - 'ReportHookData':
   *    - ... others TODO.
   * @param $task_options
   *  (optional) A further parameter to pass to the task's constructor. Its
   *  nature (or necessity) depends on the task.
   *
   * @return
   *  A new task handler object, which implements ModuleBuilderTaskInterface.
   *
   * @throws \ModuleBuilder\Exception\SanityException
   *  Throws an exception if the environment is not in a state that is ready for
   *  the requested task, for example, if no hook data has been downloaded.
   */
  public static function getTask($task_type, $task_options = NULL) {
    if (!isset(self::$environment)) {
      throw new \Exception("Environment not set.");
    }

    $task_class = self::getTaskClass($task_type);

    // Set the environment handler on the task handler too.
    $task_handler = new $task_class(self::$environment, $task_options);

    // Find out what sanity level the task handler needs.
    $required_sanity = $task_handler->getSanityLevel();
    //dsm($required_sanity);

    // Check the environment for the required sanity level.
    self::$environment->verifyEnvironment($required_sanity);

    return $task_handler;
  }

  /**
   * Helper function to get the desired Task class.
   *
   * @param $task_type
   *  The type of the task. This is the class name without the Drupal core
   *  version suffix.
   *
   * @return
   *  A fully qualified class name for the type and, if it exists, version, e.g.
   *  'ModuleBuilder\Task\Collect7'.
   */
  public static function getTaskClass($task_type) {
    $type     = ucfirst($task_type);
    $version  = self::$environment->getCoreMajorVersion();

    $versioned_class = "ModuleBuilder\\Task\\$task_type$version";
    $common_class    = "ModuleBuilder\\Task\\$task_type";

    if (class_exists($versioned_class)) {
      $class    = $versioned_class;
    }
    else {
      $class    = $common_class;
    }

    return $class;
  }

}
