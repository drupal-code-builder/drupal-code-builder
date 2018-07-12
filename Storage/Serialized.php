<?php

namespace DrupalCodeBuilder\Storage;

use DrupalCodeBuilder\Exception\StorageException;

/**
 * Provides a storage handler that uses PHP serialization in files.
 */
class Serialized extends StorageBase {

  /**
   * Stores data.
   *
   * @param string $key
   *   The indentifier for the data, e.g. 'hooks'.
   * @param array $data
   *   The data to store.
   */
  public function store($key, $data) {
    $directory = $this->environment->getHooksDirectory();
    $serialized = serialize($data);
    file_put_contents("{$directory}/{$key}_processed.php", $serialized);
  }

  /**
   * Retrieves data.
   *
   * @param string $key
   *   The indentifier for the data, e.g. 'hooks'.
   *
   * @return $data
   *   The data that was given to store().
   */
  public function retrieve($key) {
    $directory = $this->environment->getHooksDirectory();
    $data_file = "$directory/{$key}_processed.php";
    if (file_exists($data_file)) {
      $data = unserialize(file_get_contents($data_file));

      if ($data === FALSE) {
        throw new StorageException("Data file {$data_file} does not contain PHP serialized data.");
      }

      return $data;
    }

    // Sanity checks ensure we never get here, but in case they have been
    // skipped, return something that makes sense to the caller.
    return [];
  }

}
