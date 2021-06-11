<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Testing\CollectTesting7.
 */

namespace DrupalCodeBuilder\Task\Testing;

use DrupalCodeBuilder\Task\Collect;
use DrupalCodeBuilder\Factory;

/**
 * Collect hook definitions to be stored as a file in our tests folder.
 *
 * This task is meant for internal use only, to keep the testing hook
 * definitions up to date.
 */
class CollectTesting7 extends Collect {

  /**
   * {@inheritdoc}
   */
  protected function gatherHookDocumentationFiles() {
    $files = parent::gatherHookDocumentationFiles();

    // For testing, only take a subset of api.php files so we're not storing a
    // massive list of hooks.
    // TODO: this has no effect here! This method is not called here! Needs
    // to be moved to HooksCollector7!
    $testing_files = [
      'system.api.php' => TRUE,
      'block.api.php' => TRUE,
    ];

    $files = array_intersect_key($files, $testing_files);

    return $files;
  }

}
