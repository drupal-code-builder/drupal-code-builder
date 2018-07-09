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
   * (The idea is that in 3.3.x, this list will be used by UIs that can perform
   * the analysis in batches to get a complete list of the jobs, to then pass
   * back in successive batches.)
   *
   * @return array|null
   *   An array of job data, subsets of which can be passed to collect(), or
   *   NULL to indicate that this collector does not support batched analysis.
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

}
