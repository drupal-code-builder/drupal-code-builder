<?php

/**
 * @file
 * Contains the base Task class.
 */

namespace ModuleBuider\Task;

/**
 * Base class for Tasks.
 *
 * Task classes contain code to perform distinct operations. For example,
 * getting hook data by finding and processing api.php files is the domain of
 * the Collect task.
 *
 * Each task may also define the sanity level it requires. This allows the
 * Environment object to state whether the current environment is ready for the
 * task. For example, generating a module requires hook data to be ready;
 * whereas processing hook data does not.
 *
 * Task objects are instantiated by ModuleBuilderFactory::getTask().
 */
class Base {

  /**
   * Constructor.
   *
   * @param $environment
   *  The current environment handler.
   */
  function __construct($environment) {
    $this->environment = $environment;
  }

  /**
   * Get the sanity level this task requires.
   *
   * @return
   *  A sanity level string to pass to the environment's verifyEnvironment().
   */
  function getSanityLevel() {
    return $this->sanity_level;
  }

}
