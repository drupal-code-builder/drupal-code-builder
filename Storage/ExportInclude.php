<?php

namespace DrupalCodeBuilder\Storage;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Provides a storage handler that writes PHP declarations of data in files.
 *
 * Slightly better than DumpEval, as the resulting files get syntax highlighting
 * in text editors.
 */
class ExportInclude {

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
  public function store($key, $data) {
    $directory = $this->environment->getHooksDirectory();
    $export = '<?php $data =' . "\n" . var_export($data, TRUE) . ';';
    file_put_contents("{$directory}/{$key}_processed.php", $export);
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
      // Don't use include_once, in case callers are neglecting to cache their
      // data an come here several times for the same key. (Which they really
      // shouldn't, but it's not a nice way to catch the problem.)
      include $data_file;
      return $data;
    }

    // Sanity checks ensure we never get here, but in case they have been
    // skipped, return something that makes sense to the caller.
    return [];
  }

}
