<?php

/**
 * @file
 * Contains ModuleBuilder\Environment\Tests.
 */

namespace ModuleBuilder\Environment;

/**
 * Base environment class for tests.
 */
abstract class Tests extends BaseEnvironment {

  /**
   * Get a path to a module builder file or folder.
   */
  function getPath($subpath) {
    $path = dirname(__FILE__) . '/..';
    $path = $path . '/' . $subpath;
    return $path;
  }

  /**
   * Output debug data.
   */
  function debug($data, $message = '') {
    if (!empty($message)) {
      print_r($message . ':');
    }
    print_r($data);
  }

}
