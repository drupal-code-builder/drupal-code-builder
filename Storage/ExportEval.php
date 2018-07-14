<?php

namespace DrupalCodeBuilder\Storage;

/**
 * Provides a storage handler that uses PHP exported variables in files.
 *
 * This is intended for use with the sample data used by our unit tests. Yes,
 * eval() is evil, but this makes the resulting storage files human-readable.
 */
class ExportEval extends StorageBase {

  /**
   * {@inheritdoc}
   */
  public function store($key, $data) {
    $directory = $this->environment->getHooksDirectory();
    $export = var_export($data, TRUE);
    file_put_contents("{$directory}/{$key}_processed.php", $export);
  }

  /**
   * {@inheritdoc}
   */
  public function retrieve($key) {
    $directory = $this->environment->getHooksDirectory();
    $data_file = "$directory/{$key}_processed.php";
    if (file_exists($data_file)) {
      eval('$data = ' . file_get_contents($data_file) . ';');
      return $data;
    }

    return [];
  }

}
