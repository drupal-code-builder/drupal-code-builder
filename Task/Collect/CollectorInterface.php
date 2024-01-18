<?php

namespace DrupalCodeBuilder\Task\Collect;

/**
 * Interface for collector task helpers.
 */
interface CollectorInterface {

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
  public function getJobList();

  /**
   * Collect the data.
   *
   * @param $job_list
   *   The array of job data from getJobList();
   *
   * @return array
   *   The array of analysis data.
   */
  public function collect($job_list);

}
