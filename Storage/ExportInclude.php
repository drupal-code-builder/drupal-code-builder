<?php

namespace DrupalCodeBuilder\Storage;

use DrupalCodeBuilder\Exception\StorageException;

/**
 * Provides a storage handler that writes PHP declarations of data in files.
 *
 * Slightly better than ExportEval, as the resulting files get syntax
 * highlighting in text editors.
 */
class ExportInclude extends StorageBase {

  /**
   * {@inheritdoc}
   */
  public function store($key, $data) {
    $directory = $this->environment->getDataDirectory();
    $export = '<?php $data =' . "\n" . var_export($data, TRUE) . ';';
    file_put_contents("{$directory}/{$key}_processed.php", $export);
  }

  /**
   * {@inheritdoc}
   */
  public function retrieve($key) {
    $directory = $this->environment->getDataDirectory();

    // Convert the public:// scheme to an absolute path, as using include on
    // a stream on PHP ^7.4 causes it to call streamWrapper::stream_set_option()
    // which for the public:// wrapper on Drupal causes a warning in
    // LocalStream::stream_set_option().
    $directory = str_replace(
      "public://",
      \Drupal::service('file_system')->realpath("public://") . '/',
      $directory
    );

    $data_file = "$directory/{$key}_processed.php";
    if (file_exists($data_file)) {
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

    // Incremental code analysis will call this method before anything has been
    // saved, so if the file doesn't exist just return an empty array.
    return [];
  }

}
