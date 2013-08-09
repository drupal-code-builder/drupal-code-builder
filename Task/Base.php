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

}
