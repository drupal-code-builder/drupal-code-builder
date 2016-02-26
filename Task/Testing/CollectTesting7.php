<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Testing\CollectTesting7.
 */

namespace DrupalCodeBuilder\Task\Testing;

use DrupalCodeBuilder\Task\Collect7;
use DrupalCodeBuilder\Factory;

/**
 * Collect hook definitions to be stored as a file in our tests folder.
 *
 * This task is meant for internal use only, to keep the testing hook
 * definitions up to date.
 */
class CollectTesting7 extends Collect7 {

  /**
   * {@inheritdoc}
   */
  protected function gatherHookDocumentationFiles() {
    $files = parent::gatherHookDocumentationFiles();

    // For testing, only take a subset of api.php files so we're not storing a
    // massive list of hooks.
    $testing_files = array(
      'system.api.php' => TRUE,
      'block.api.php' => TRUE,
    );

    $files = array_intersect_key($files, $testing_files);

    return $files;
  }

  /**
   * {@inheritdoc}
   */
  protected function writeProcessedData($data, $type) {
    // Write the processed data to a file in our testing folder.
    $directory = Factory::getLibraryBaseDirectory()
      . '/tests/sample_hook_definitions/'
      . $this->environment->getCoreMajorVersion();
    $serialized = serialize($data);
    file_put_contents("{$directory}/{$type}_processed.php", $serialized);
  }

}
