<?php

namespace DrupalCodeBuilder\Environment;

/**
 * Environment class for writing sample hook data for use by unit tests.
 *
 * Works with the --test option to the drush cbu command.
 */
class WriteTestsSampleLocation extends Drush {

  /**
   * The short class name of the storage helper to use.
   */
  protected $storageType = 'ExportInclude';

  /**
   * Indicates that the Collect task should filter for sample data.
   *
   * This is accessed by the Collect task helpers.
   *
   * TODO: change all Collect task code to use this and remove the
   * CollectTesting* classes.
   */
  public $sample_data_write = TRUE;

  /**
   * Set the hooks directory.
   *
   * TODO: define this in a trait to keep the directory name DRY.
   */
  function getHooksDirectorySetting() {
    // Set the folder for the hooks. This contains a prepared file for the tests
    // to use.
    // TODO: use Factory::getLibraryBaseDirectory()
    $directory = dirname(dirname(__FILE__)) . '/Test/sample_hook_definitions/' . $this->getCoreMajorVersion();

    $this->hooks_directory = $directory;
  }

}
