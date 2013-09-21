<?php

/**
 * @file
 * Contains the ModuleBuilderFactory class.
 *
 * This file should/could be somewhere else for autoloading, but the core issue
 * regarding PSR-4/X is still ongoing (https://drupal.org/node/1971198) and I'd
 * rather move files and rename classes once rather than twice.
 */

/**
 * Helper to get a Module Builder factory.
 *
 * (Which makes it a factory factory? Ouch my head...)
 *
 * This is the entry point to Module Builder.
 *
 * @param $environment_class
 *  The name of the environment class to set on the factory. No checks are run
 *  on the environment at this stage (and therefore the environment handler may
 *  be flagged to skip checks via the returned factory). This parameter may be
 *  ommitted if the the caller is certain that the factory has previously been
 *  created and it is just calling this to retrieve it.
 *
 * @return
 *  A new ModuleBuilderFactory object if called for the first time, or the same
 *  factory object as last time on subsequent calls. The environment is
 *  available in the 'environment' property of the factory.
 */
function module_builder_get_factory($environment_class = NULL) {
  static $factory;

  if (!isset($factory)) {
    // Include old procedural include files.
    include_once(dirname(__FILE__) . '/includes/common.inc');

    // Include the environment classes file.
    include_once(dirname(__FILE__) . '/Environment/Environment.php');

    // Create the environment handler and set it on the factory.
    // You did pass in the environment class, didn't you?
    $environment = new $environment_class;

    $factory = new ModuleBuilderFactory($environment);
  }

  return $factory;
}

/**
 * Factory class for creating Module Builder task handlers.
 *
 * A Task Handler is an object which provides a public API which can be used by
 * different UIs for performing various module builder tasks.
 *
 * The process for using this factory is:
 *  - Get the factory from module_builder_get_factory(), passing the name of the
 *    environment class that represents where you are using Module Builder.
 *  - Get the task handler. This checks the environment to see whether it is
 *    suitably set up for the requested task (eg, whether the hooks directory
 *    exists, whether hook data has been compiled). This throws an exception if
 *    the environment is not in a state the task requires.
 *  - Execute the required method on the task handler.
 */
class ModuleBuilderFactory {

  /**
   * The current environment object; subclass of ModuleBuilderEnvironmentBase.
   *
   * Set by the constructor. May be freely accessed.
   */
  public $environment;

  /**
   * Constructor.
   *
   * We set the environment on the factory now, so that getTask() can make use
   * of it later.
   *
   * @param ModuleBuilderEnvironment $environment
   *  A new environment object.
   */
  function __construct($environment) {
    $this->environment = $environment;
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
   *  ModuleBuider\Task namespace. May be one of:
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
   * @throws ModuleBuilderException
   *  Throws an exception if the environment is not in a state that is ready for
   *  the requested task, for example, if no hook data has been downloaded.
   */
  function getTask($task_type, $task_options = NULL) {
    // TODO: this could do with namespacing and autoloading in due course.
    include_once(dirname(__FILE__) . "/Task/Base.php");
    include_once(dirname(__FILE__) . "/Task/$task_type.php");

    $task_class = "ModuleBuider\Task\\$task_type";

    // Set the environment handler on the task handler too.
    $task_handler = new $task_class($this->environment, $task_options);

    // Find out what sanity level the task handler needs.
    $required_sanity = $task_handler->getSanityLevel();
    //dsm($required_sanity);

    // Check the environment for the required sanity level.
    $this->environment->verifyEnvironment($required_sanity);

    return $task_handler;
  }

}
