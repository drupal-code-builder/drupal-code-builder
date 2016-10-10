<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Factory.
 */

namespace DrupalCodeBuilder;

use DrupalCodeBuilder\Exception\InvalidInputException;

// Include the Composer autoloader for our classes.
// We use this if we're not being used as a Composer library, for example if
// installed as a Drupal library in sites/all/libraries, or running as a Drupal
// module.
// If we're being used as a Composer library, then we must skip this, as
// Composer's autoloader uses 'require' rather than 'require_once' and thus PHP
// would crash due to redeclaration of our classes.
// We use one of our classes as a litmus test: if it can be found already, then
// our autoloading has already been registered by a host application's Composer.
if (!class_exists('\DrupalCodeBuilder\Environment\BaseEnvironment')) {
  require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Static factory class for creating Drupal Code Builder task handlers.
 *
 * A Task Handler is an object which provides a public API which can be used by
 * different UIs for performing various module builder tasks.
 *
 * The process for using this factory is:
 *  - Set the environment that represents where you are using Drupal Code
 *    Builder, such as Drush or Drupal.
 *  - Get the task handler. This checks the environment to see whether it is
 *    suitably set up for the requested task (e.g., whether the hooks directory
 *    exists, whether hook data has been compiled). This throws an exception if
 *    the environment is not in a state the task requires.
 *  - Execute the required method on the task handler.
 *
 * For example:
 * @code
 *  include_once('path/to/Factory.php');
 *  \DrupalCodeBuilder\Factory::setEnvironmentLocalClass('Drush')
 *    ->setCoreVersionNumber(8);
 *  $task = \DrupalCodeBuilder\Factory::getTask('ReportHookData');
 * @endcode
 */
class Factory {

  /**
   * The current environment object; subclass of DrupalCodeBuilderEnvironmentBase.
   *
   * @see setEnvironmentLocalClass()
   * @see getEnvironment()
   */
  protected static $environment;

  /**
   * Set the environment object.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *  An environment object to set.
   *
   * @return
   *  The environment object. This should then have setCoreVersionNumber()
   *  called on it.
   */
  public static function setEnvironment($environment) {
    self::$environment = $environment;
    return self::$environment;
  }

  /**
   * Set the environment using a class name.
   *
   * This is a convenience wrapper around setEnvironment() for when using an
   * environment class native to Drupal Code Builder.
   *
   * @param $environment_class
   *  The name of the environment class to set on the factory, relative to the
   *  \DrupalCodeBuilder\Environment namespace (to use a class outside of that
   *  namespace, use setEnvironment()). No checks are run on the environment
   *  at this stage (and therefore the environment handler may be flagged to
   *  skip checks via the returned factory).
   *
   * @return
   *  The environment object. This should then have setCoreVersionNumber()
   *  called on it.
   */
  public static function setEnvironmentLocalClass($environment_class) {
    // Create the environment handler and set it on the factory.
    $environment_class = '\DrupalCodeBuilder\Environment\\' . $environment_class;
    self::$environment = new $environment_class;

    return self::$environment;
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
   *  DrupalCodeBuilder\Task namespace, without the Drupal core version suffix.
   *  May be one of:
   *    - 'Collect': Collect and process data on available hooks.
   *    - 'ReportHookData':
   *    - ... others TODO.
   * @param $task_options
   *  (optional) A further parameter to pass to the task's constructor. Its
   *  nature (or necessity) depends on the task.
   *
   * @return
   *  A new task handler object, which implements DrupalCodeBuilderTaskInterface.
   *
   * @throws \DrupalCodeBuilder\Exception\SanityException
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
   *  'DrupalCodeBuilder\Task\Collect7'.
   *
   * @throws
   *  Throws \DrupalCodeBuilder\Exception\InvalidInputException if the task type
   *  does not correspond to a Task class.
   */
  public static function getTaskClass($task_type) {
    $type     = ucfirst($task_type);
    $version  = self::$environment->getCoreMajorVersion();

    $versioned_class = "DrupalCodeBuilder\\Task\\$task_type$version";
    $common_class    = "DrupalCodeBuilder\\Task\\$task_type";

    if (class_exists($versioned_class)) {
      $class    = $versioned_class;
    }
    elseif (class_exists($common_class)) {
      $class    = $common_class;
    }
    else {
      throw new InvalidInputException("Task class not found.");
    }

    return $class;
  }

  /**
   * Returns the base directory Drupal Code Builder is installed in.
   *
   * @return
   *  The base directory path.
   */
  public static function getLibraryBaseDirectory() {
    return __DIR__;
  }

}
