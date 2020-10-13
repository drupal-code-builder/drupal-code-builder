<?php

namespace DrupalCodeBuilder\Environment;

/**
 * This exists so PHP-DI has a non-abstract class to use for the environment.
 */
class DefaultEnvironment extends BaseEnvironment {

  public function __construct() {
    // Complain, because this class should be replaced on the container with
    // the actual environment before the container is used.
    throw new \Exception("This class should not be instantiated!");
  }

  public function debug($data, $message = '') {
  }

}
