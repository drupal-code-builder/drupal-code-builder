<?php

/**
 * @file
 * Contains ModuleBuilder\Factory.
 */

namespace ModuleBuilder;

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
 *  include_once('path/to/ModuleBuilderFactory.php');
 *  \ModuleBuilder\Factory::setEnvironmentClass('Drush');
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
   *
   * @return
   *  The environment object.
   */
  public static function setEnvironmentClass($environment_class) {
    // Include the environment interface and classes files.
    include_once(__DIR__ . '/Environment/EnvironmentInterface.php');
    include_once(__DIR__ . '/Environment/Environment.php');

    // Create the environment handler and set it on the factory.
    $environment_class = '\ModuleBuilder\Environment\\' . $environment_class;

    self::setEnvironment(new $environment_class);

    return self::$environment;
  }

  /**
   * Set the environment object.
   *
   * @param \ModuleBuilder\Environment\EnvironmentInterface $environment
   *  An environment object to set.
   */
  public static function setEnvironment(\ModuleBuilder\Environment\EnvironmentInterface $environment) {
    self::$environment = new $environment;
  }

  /**
   * Get the environment object.
   *
   * @return
   *  The environment object.
   */
  public static function getEnvironment() {
    if (!isset(self::$environment)) {
      throw new Exception("Environment not set.");
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
   *  ModuleBuider\Task namespace, without the Drupal core version suffix.
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
   * @throws \ModuleBuilder\Exception
   *  Throws an exception if the environment is not in a state that is ready for
   *  the requested task, for example, if no hook data has been downloaded.
   */
  public static function getTask($task_type, $task_options = NULL) {
    if (!isset(self::$environment)) {
      throw new Exception("Environment not set.");
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
   * Takes care of including files for base class and non-version-specific
   * class as well as the class itself.
   *
   * @param $task_type
   *  The type of the task. This is the class name without the Drupal core
   *  version suffix.
   *
   * @return
   *  A fully qualified class name for the type and, if it exists, version, e.g.
   *  'ModuleBuider\Task\Collect7'.
   */
  public static function getTaskClass($task_type) {
    $type     = ucfirst($task_type);
    $version  = self::$environment->getCoreMajorVersion();

    // TODO: this could do with namespacing and autoloading in due course.
    include_once(dirname(__FILE__) . "/Task/Base.php");

    $versioned_filepath = dirname(__FILE__) . "/Task/$task_type$version.php";
    $common_filepath    = dirname(__FILE__) . "/Task/$task_type.php";

    // Always include the unversioned filepath; it is the parent class for
    // different versions.
    include_once($common_filepath);

    if (file_exists($versioned_filepath)) {
      include_once($versioned_filepath);

      $class    = 'ModuleBuider\\Task\\' . $task_type . $version;
    }
    else {
      $class    = 'ModuleBuider\\Task\\' . $task_type;
    }

    return $class;
  }

}

/**
 * Custom exception class.
 */
class Exception extends \Exception {
  // Flag set to TRUE if hook data needs downloading (and the folders are ok).
  // This allows us to recover gracefully.
  public $needs_hooks_download;
}
