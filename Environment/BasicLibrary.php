<?php

/**
 * @file
 * Contains ModuleBuilder\Environment\BasicLibrary.
 */

namespace ModuleBuilder\Environment;

/**
 * Environment class for use as a library, without Libraries API.
 */
class BasicLibrary extends BaseEnvironment {

  /**
   * Output debug data.
   */
  function debug($data, $message = '') {
    if (function_exists('dpm')) {
      dpm($data, $message);
    }
  }

}
