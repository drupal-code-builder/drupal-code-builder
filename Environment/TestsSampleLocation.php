<?php

/**
 * @file
 * Contains ModuleBuilder\Environment\TestsSampleLocation.
 */

namespace ModuleBuilder\Environment;

/**
 * Environment class for tests using prepared sample hook data.
 */
class TestsSampleLocation extends Tests {

  /**
   * Set the hooks directory.
   */
  function getHooksDirectorySetting() {
    // Set the folder for the hooks. This contains a prepared file for the tests
    // to use.
    $directory = dirname(dirname(__FILE__)) . '/tests/sample_hook_definitions/' . $this->getCoreMajorVersion();

    $this->hooks_directory = $directory;
  }

}
