<?php

namespace DrupalCodeBuilder\Storage;

/**
 * Storage that stores in memory.
 *
 * This is used for collection tests, so they don't write any files.
 */
class Memory {

  protected $storage = [];

  /**
   * {@inheritdoc}
   */
  public function store($key, $data) {
    $this->storage[$key] = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function retrieve($key) {
    return $this->storage[$key] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
  }

}
