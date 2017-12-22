<?php

namespace DrupalCodeBuilder\Environment;

/**
 * Environment class for tests using prepared sample hook data.
 */
class TestsSampleLocation extends Tests {

  /**
   * The short class name of the storage helper to use.
   */
  protected $storageType = 'ExportInclude';

  /**
   * Set the hooks directory.
   */
  function getHooksDirectorySetting() {
    // Set the folder for the hooks. This contains a prepared file for the tests
    // to use.
    $directory = dirname(dirname(__FILE__)) . '/Test/sample_hook_definitions/' . $this->getCoreMajorVersion();

    $this->hooks_directory = $directory;
  }

}
