<?php

namespace DrupalCodeBuilder\Storage;

use DrupalCodeBuilder\Exception\StorageException;

/**
 * Provides a storage handler that uses PHP serialization in files.
 */
class Serialized extends StorageBase {

  /**
   * {@inheritdoc}
   */
  public function store($key, $data) {
    $directory = $this->environment->getHooksDirectory();
    $serialized = serialize($data);
    file_put_contents("{$directory}/{$key}_processed.php", $serialized);
  }

  /**
   * {@inheritdoc}
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

    return [];
  }

}
