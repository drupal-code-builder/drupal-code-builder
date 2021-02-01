<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Factory.
 */

namespace DrupalCodeBuilder;

use DrupalCodeBuilder\Exception\InvalidInputException;
use CaseConverter\CaseString;

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
   * The service container.
   *
   * @var \Psr\Container\ContainerInterface
   */
  protected static $container;

  /**
   * The current environment object; subclass of DrupalCodeBuilderEnvironmentBase.
   *
   * @see setEnvironmentLocalClass()
   * @see getEnvironment()
   */
  protected static $environment;

  /**
   * Gets the container.
   *
   * @internal
   */
  public static function getContainer() {
    if (!static::$container) {
      $cached_file = realpath(__DIR__ . '/DependencyInjection/cache/DrupalCodeBuilderCompiledContainer.php');
      if (file_exists($cached_file)) {
        include_once($cached_file);

        static::$container = new \DrupalCodeBuilderCompiledContainer();
      }
      else {
        static::$container = \DrupalCodeBuilder\DependencyInjection\ContainerBuilder::buildContainer();
      }
    }

    return static::$container;
  }

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
    // Zap an existing container, as that will have instantiated
    // version-specific services.
    static::$container = NULL;

    $container = static::getContainer();

    // Set the environment on the container, but note that it is not ready yet
    // because setEnvironment() is typically called without the version helper
    // having been set on it yet.
    $container->set('environment', $environment);

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
    $environment = new $environment_class;

    // Call setEnvironment(), as that takes care of setting the environment on
    // the container.
    return self::setEnvironment($environment);
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
   * This creates a new task handler object as requested.
   *
   * If a class exists named '$task_typeX' where X is the Drupal core major
   * version number set in the environment, then that class is returned instead
   * of the unversioned class.
   *
   * Base tasks have the environment checked for the environment level the
   * task declares that it needs.
   *
   * @param $task_type
   *  The type of task. This should the the name of a class in the
   *  DrupalCodeBuilder\Task namespace or a child namespace, with that namespace
   *  prefix removed and without any Drupal core version suffix.
   *  For example:
   *    - 'Collect'
   *    - 'ReportHookData'
   *    - 'Generate\ComponentClassHandler'
   * @param $task_options
   *  (optional) A further parameter to pass to the task's constructor. Its
   *  nature (or necessity) depends on the task.
   *
   * @return
   *  The task handler object.
   *
   * @throws \DrupalCodeBuilder\Exception\SanityException
   *  Throws an exception if the environment is not in a state that is ready for
   *  the requested task, for example, if no hook data has been downloaded.
   */
  public static function getTask($task_type, $task_options = NULL) {
    if (!isset(self::$environment)) {
      throw new \Exception("Environment not set.");
    }

    // The Generate task is handled specially, as it needs the component type
    // passing to the container.
    if ($task_type == 'Generate') {
      $component_type = $task_options;
      $task_handler = static::getContainer()->get('Generate|' . $component_type);
    }
    else {
      // Only the Generate task should be using this parameter.
      assert(is_null($task_options));

      $task_handler = static::getContainer()->get($task_type);
    }

    // Base-level tasks get their sanity checked.
    if (is_a($task_handler, \DrupalCodeBuilder\Task\Base::class, TRUE)) {
      // Find out what sanity level the task handler needs.
      $required_sanity = $task_handler->getSanityLevel();

      // Check the environment for the required sanity level.
      self::$environment->verifyEnvironment($required_sanity);
    }

    return $task_handler;
  }

  /**
   * Helper function to get the desired Task class.
   *
   * @deprecated
   *   Will be removed in 5.0.0.
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
