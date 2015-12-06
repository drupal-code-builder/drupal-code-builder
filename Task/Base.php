<?php

/**
 * @file
 * Contains ModuleBuilder\Task.
 */

namespace ModuleBuilder\Task;

/**
 * Base class for Tasks.
 *
 * Task classes contain code to perform distinct operations. For example,
 * getting hook data by finding and processing api.php files is the domain of
 * the Collect task.
 *
 * Public methods on Task classes are part of Module Builder's public API, and
 * may be considered stable.
 *
 * Each task may also define the sanity level it requires. This allows the
 * Environment object to state whether the current environment is ready for the
 * task. For example, generating a module requires hook data to be ready;
 * whereas processing hook data does not.
 *
 * Task classes may be specialized for different major versions of Drupal, by
 * appending the version number to the class name. The unversioned class should
 * also exist as a parent class.
 *
 * Task objects should be instantiated by \ModuleBuilder\Factory::getTask().
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
   *
   * @see \ModuleBuilder\Environment\EnvironmentInterface::verifyEnvironment()
   */
  function getSanityLevel() {
    return $this->sanity_level;
  }

}
