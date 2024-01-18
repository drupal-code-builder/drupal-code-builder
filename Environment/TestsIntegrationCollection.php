<?php

namespace DrupalCodeBuilder\Environment;

/**
 * Base environment class for collection integration tests.
 */
class TestsIntegrationCollection extends BaseEnvironment {

  /**
   * Get the hooks directory setting from the environment and set it locally.
   */
  public function getDataDirectory() {
    // Just put our files in the top-level files directory to save mucking about
    // creating a subfolder.
    return 'public://';
  }

  /**
   * Output debug data.
   */
  public function debug($data, $message = '') {
    if (!empty($message)) {
      dump($message . ':');
    }
    dump($data);
  }

}
