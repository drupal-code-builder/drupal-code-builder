<?php

namespace DrupalCodeBuilder\Storage;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Base class for storage handlers.
 */
abstract class StorageBase {

  /**
   * The environment object.
   *
   * @var \DrupalCodeBuilder\Environment\EnvironmentInterface
   */
  protected $environment;

  /**
   * Constructs a new helper.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment object.
   */
  public function __construct(
    EnvironmentInterface $environment
  ) {
    $this->environment = $environment;
  }

  /**
   * Stores data.
   *
   * @param string $key
   *   The indentifier for the data, e.g. 'hooks'.
   * @param array $data
   *   The data to store.
   */
  abstract public function store($key, $data);

  /**
   * Retrieves data.
   *
   * @param string $key
   *   The indentifier for the data, e.g. 'hooks'.
   *
   * @return $data
   *   The data that was given to store(), or an empty array if the file does
   *   not exist. (This is because incremental code analysis will call this
   *   method before anything has been saved, so if the file doesn't exist we
   *   just return an empty array.)
   */
  abstract public function retrieve($key);

  /**
   * Deletes a data file, if it exists.
   *
   * Does not give any indication if the deletion failed.
   *
   * @param string $key
   *   The indentifier for the data, e.g. 'hooks'.
   */
  public function delete($key) {
    $directory = $this->environment->getHooksDirectory();
    $data_file = "$directory/{$key}_processed.php";

    if (file_exists($data_file)) {
      unlink($data_file);
    }
  }

}
