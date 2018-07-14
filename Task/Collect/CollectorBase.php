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
   * Produces the list of jobs for this collector.
   *
   * This is used by collectComponentDataIncremental() to give UIs a list of
   * jobs they can then call in batches, and internally by
   * collectComponentData() to simply perform the whole analysis in one go.
   *
   * @return array|null
   *   A numeric array of job data, subsets of which can be passed to collect(),
   *   or NULL to indicate that this collector does not support batched
   *   analysis. Each job item in the array should itself be an array of data.
   *   The elements in the array are internal to the Collector class, except for
   *   the following keys:
   *   - 'process_label': Should be set to a human-readable string describing
   *     the process being run. This should be the same for each job from a
   *     particular collector.
   *   - 'item_label': Should be set to a human-readable string for the
   *     particular item.
   *   - 'collector': Reserved for Collect task use.
   *   - 'last': Reserved for Collect task use.
   *   Collector classes should perform
   *   sorting in this method, rather than later on during collect().
   */
  abstract public function getJobList();

  /**
   * Collect the data.
   *
   * @param $job_list
   *   The array of job data from getJobList();
   *
   * @return array
   *   The array of analysis data.
   */
  abstract public function collect($job_list);

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
