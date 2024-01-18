<?php

namespace DrupalCodeBuilder\Task\Collect;

/**
 * Base class for analysis collector classes.
 */
abstract class CollectorBase implements CollectorInterface {

  /**
   * The environment object.
   *
   * @var \DrupalCodeBuilder\Environment\EnvironmentInterface
   */
  protected $environment;

  /**
   * The key in the filename for the processed data.
   *
   * Child classes must override this.
   *
   * @var string
   */
  protected $saveDataKey;

  /**
   * The human-readable string to use in the report message.
   *
   * Child classes must override this.
   *
   * @var string
   */
  protected $reportingString;

  /**
   * The data from this collector to store in testing sample data.
   *
   * This should be an array of IDs that match keys in the array of data this
   * collector returns from collect().
   *
   * Collectors that need to filter in specialised ways should leave this as an
   * empty array.
   *
   * @see static::getTestingIds()
   *
   * @var array
   */
  protected $testingIds = [];

  /**
   * Gets the filename key for the processed data.
   *
   * This can be used for a Storage's store() method.
   *
   * @return string
   */
  public final function getSaveDataKey() {
    return $this->saveDataKey;
  }

  /**
   * Gets the string to use in a report message.
   *
   * @return string
   */
  public final function getReportingKey() {
    return $this->reportingString;
  }

  /**
   * Gets the data IDs this collector should store for testing sample data.
   *
   * @return array
   *   An array of data IDs to filter by.
   */
  public final function getTestingIds(): array {
    return $this->testingIds;
  }

  /**
   * Gets the count of items in an array of data.
   *
   * @param array $data
   *   An array of analysis data.
   *
   * @return int
   */
  public function getDataCount($data) {
    return count($data);
  }

  /**
   * Find files matching a regex in modules and Core components.
   *
   * @param string $mask
   *   A regex to match with, WITHOUT the regex delimiters.
   *
   * @return array
   *   A numeric array of arrays of file info.
   */
  protected function findFiles(string $mask): array {
    $files = [];

    $system_listing = \DrupalCodeBuilder\Factory::getEnvironment()->systemListing("@$mask@", 'modules', 'filename');
    foreach ($system_listing as $filename => $file) {
      $files[] = (array) $file;
    }

    $core_directory_iterator = new \RecursiveDirectoryIterator('core/lib/Drupal');
    $recursive_iterator = new \RecursiveIteratorIterator($core_directory_iterator);
    // We need to make the regex grab everything from the start to get the
    // whole relative pathname in the result.
    $regex_iterator = new \RegexIterator($recursive_iterator, "@^.+$mask@", \RecursiveRegexIterator::GET_MATCH);
    foreach ($regex_iterator as $regex_files) {
      foreach ($regex_files as $file) {
        $filename = basename($file);

        // TODO: component name, which gets complicated as we might find things
        // in /Core **or** /Component!
        // $component_name = explode('.', $filename)[0];

        $files[] = [
          'uri' => $file,
          'filename' => basename($file),
          'name' => basename($file, '.php'),
          'module' => 'core',
          'item_label' => $filename,
        ];
      }
    }
    return $files;
  }

  /**
   * Gets the first line from a docblock string.
   *
   * @param string $docblock
   *   The docblock as a single string.
   *
   * @return string
   *   The first line, without the '*' or indent.
   */
  protected function getDocblockFirstLine(string $docblock): string {
    $method_docblock_lines = explode("\n", $docblock);
    foreach ($method_docblock_lines as $line) {
      // Take the first actual docblock line to be the description.
      $matches = [];
      if (preg_match('@^ +\* (.+)@', $line, $matches)) {
        $description = $matches[1];
        break;
      }
    }

    return $description ?? '';
  }

  /**
   * Merge incrementally collected data.
   *
   * @param array $existing_data
   *   An array of data in the format returned by collect().
   * @param array $new_data
   *   An array of data in the format returned by collect().
   *
   * @return
   *   The result of the merge.
   */
  public function mergeComponentData($existing_data, $new_data) {
    // Top-level merge is fine for collectors that just return an array of data
    // keyed by an ID.
    $merged_data = array_merge($existing_data, $new_data);

    return $merged_data;
  }

}
