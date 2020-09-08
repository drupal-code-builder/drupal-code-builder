<?php

namespace DrupalCodeBuilder\Storage;

use DrupalCodeBuilder\Exception\StorageException;

/**
 * Provides a storage handler for tests.
 *
 * This uses include as well, but doesn't need Drupal's filesystem service.
 */
class TestExportInclude extends ExportInclude {

  /**
   * {@inheritdoc}
   */
  public function retrieve($key) {
    $directory = $this->environment->getHooksDirectory();

    $data_file = "$directory/{$key}_processed.php";

    if (!file_exists($data_file)) {
      // For a temporary file, behave normally, and return empty data if it
      // doesn't exist.
      if (strpos($key, 'temporary') !== FALSE) {
        return [];
      }

      // For a permanent file, throw an exception, because in this case
      // something is wrong and tests should fail.
      throw new StorageException("Data file {$data_file} does not exist.");
    }

    // Don't use include_once, in case callers are neglecting to cache their
    // data an come here several times for the same key. (Which they really
    // shouldn't, but it's not a nice way to catch the problem.)
    include $data_file;

    // The included file must declare the $data variable.
    if (!isset($data)) {
      throw new StorageException("Included data file {$data_file} did not execute correctly as PHP.");
    }

    return $data;
  }

}
