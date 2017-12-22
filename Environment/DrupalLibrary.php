<?php

namespace DrupalCodeBuilder\Environment;

/**
 * Environment class for use as a library, without Libraries API.
 */
class DrupalLibrary extends BaseEnvironment {

  /**
   * Output debug data.
   */
  public function debug($data, $message = '') {
    if (function_exists('dpm')) {
      dpm($data, $message);
    }
  }

}
