<?php

/**
 * @file
 * Contains the base Task class.
 */

namespace ModuleBuider\Task;

/**
 * Base class for Tasks.
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
