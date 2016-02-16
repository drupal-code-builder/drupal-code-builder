<?php

/**
 * @file
 * Contains ModuleBuilder\Environment\TestsTempLocation.
 */

namespace ModuleBuilder\Environment;

/**
 * Environment class for tests writing hook data to the Drupal's temp folder.
 */
class TestsTempLocation extends Tests {

  /**
   * Set the hooks directory.
   */
  function getHooksDirectorySetting() {
    // Set the folder for the hooks. This contains a prepared file for the tests
    // to use.
    // By some magic this appears to be safe to use with DrupalUnitTestCase.
    $directory = file_directory_temp() . '/module_builder_hook_definitions/' . $this->getCoreMajorVersion();

    $this->hooks_directory = $directory;
  }

}
