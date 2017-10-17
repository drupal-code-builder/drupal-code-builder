<?php

namespace DrupalCodeBuilder\Environment;

/**
 * Environment class for writing sample hook data for use by unit tests.
 *
 * Works with the --test option to the drush cbu command.
 */
class WriteTestsSampleLocation extends Drush {

  /**
   * Set the hooks directory.
   *
   * TODO: define this in a trait to keep the directory name DRY.
   */
  function getHooksDirectorySetting() {
    // Set the folder for the hooks. This contains a prepared file for the tests
    // to use.
    $directory = dirname(dirname(__FILE__)) . '/Test/sample_hook_definitions/' . $this->getCoreMajorVersion();

    $this->hooks_directory = $directory;
  }

}
