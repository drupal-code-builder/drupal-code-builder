<?php

namespace DrupalCodeBuilder\Task\Collect;

/**
 * Base class for analysis collector classes.
 */
abstract class CollectorBase {

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
   * Collect the data.
   *
   * @return array
   */
  abstract public function collect();

}
