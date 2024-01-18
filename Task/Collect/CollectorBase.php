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
