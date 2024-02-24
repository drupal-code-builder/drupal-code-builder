<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Collect.
 */

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Attribute\InjectImplementations;
use DrupalCodeBuilder\Task\Collect\CollectorInterface;

/**
 * Task handler for collecting and processing definitions for Drupal components.
 *
 * This class is for the highest supported version of Drupal, and any lower
 * versions which do not have a dedicated class.
 *
 * The collection process will do different things depending on the core Drupal
 * version:
 *  - on D5/6, this downloads documentation files from drupal.org containing
 *    definitions of hooks.
 *  - on D7, this collects hook documentation files from the current site.
 *  - on D8 and higher, this collects data about plugins as well as hooks.
 */
class Collect extends Base {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'data_directory_exists';

  /**
   * The collector services.
   *
   * @var array
   */
  protected $collectors = [];

  /**
   * Sets the collector services.
   *
   * Called by the container on instantiation.
   *
   * @param array $collectors
   *  An array of collector tasks. These are all the
   *  services which implement
   *  \DrupalCodeBuilder\Task\Collect\CollectorInterface.
   *
   * @see \DrupalCodeBuilder\DependencyInjection\ContainerBuilder
   */
  #[InjectImplementations(CollectorInterface::class)]
  public function setCollectors(array $collectors) {
    $this->collectors = $collectors;
  }

  /**
   * Get the list of analysis jobs, to use for incremental analysis.
   *
   * @return array
   *   A numeric array of jobs. Each job is itself an array, whose details are
   *   internal except for the following keys:
   *   - 'process_label': A human-readable string describing the process being
   *     run. This should be the same for each job from a particular collector.
   *   - 'item_label': A human-readable string for the particular item. This
   *     will be empty if the process has only a single job.
   */
  public function getJobList() {
    $job_list = [];

    foreach ($this->collectors as $collector_service_name => $collector_helper) {
      // Get the list of jobs from each collector.
      $collector_job_list = $collector_helper->getJobList();
      assert(!empty($collector_helper->getReportingKey()));

      if (is_null($collector_job_list)) {
        // Collector doesn't support jobs: create just a single job for it.
        $job_list[] = [
          'collector' => $collector_service_name,
          'process_label' => $collector_helper->getReportingKey(),
          // A singleton job is by definition the last one.
          // Simpler to do this and have the analysis waste time trying to load
          // a temporary file than add special handling for singleton jobs.
          'last' => TRUE,
        ];
      }
      else {
        array_walk($collector_job_list, function(&$item) use ($collector_service_name) {
          $item['collector'] = $collector_service_name;
        });

        // Mark the final one so we know when to write the data to the
        // permanent location.
        $keys = array_keys($collector_job_list);
        $last_index = end($keys);
        $collector_job_list[$last_index]['last'] = TRUE;

        $job_list = array_merge($job_list, $collector_job_list);
      }
    }

    return $job_list;
  }

  /**
   * Perform a batch of incremental analysis.
   *
   * @param array $job_list
   *   A subset of the job list returned by getJobList().
   * @param array &$results
   *   An array of results, passed by reference, into which an ongoing summary
   *   of the analysis is set. Once all jobs have been passed into successive
   *   calls of this method, this parameter will be identical to the return
   *   value of collectComponentData().
   */
  public function collectComponentDataIncremental($job_list, &$results) {
    $exceptions_caught = [];

    // Populate an array of incremental data that we collect for the given job
    // list, so array_merge() works.
    $incremental_data = array_fill_keys(array_keys($this->collectors), []);

    // Keep track of any jobs which are the last for their collector, so we
    // know to write to the real storage file rather than temporary.
    $final_jobs = [];

    $grouped_jobs = [];
    // Group the jobs by collector.
    foreach ($job_list as $job) {
      $collector_service_name = $job['collector'];
      $grouped_jobs[$collector_service_name][] = $job;

      if (!empty($job['last'])) {
        $final_jobs[$collector_service_name] = TRUE;
      }
    }

    // Pass each set of jobs to its respective collective.
    foreach ($grouped_jobs as $collector_service_name => $jobs) {
      $collector_helper = $this->collectors[$collector_service_name];

      try {
        $incremental_data[$collector_service_name] = $collector_helper->collect($jobs);
      }
      catch (\Exception $e) {
        // Catch and hold an exception, so that the next collector helper is
        // not skipped.
        // TODO: Make this more granular, as this could still mean some of the
        // current collector's jobs are skipped.
        $exceptions_caught[] = $e;
      }

      // Filter the data if we're collecting sample data for tests.
      if (!empty($this->environment->sample_data_write)) {
        if ($testing_ids = $collector_helper->getTestingIds()) {
          $filter = array_flip($testing_ids);

          $incremental_data[$collector_service_name] = array_intersect_key($incremental_data[$collector_service_name], $filter);
        }
      }
    }

    // Save the data for each collector.
    $storage = $this->environment->getStorage();
    foreach ($incremental_data as $collector_service_name => $collector_incremental_data) {
      // We have a key for every collector in $incremental_data, but have not
      // necessarily run jobs for that collector in this batch. Skip if empty
      // and we're not on the last job.
      if (empty($collector_incremental_data) && !isset($final_jobs[$collector_service_name])) {
        continue;
      }

      $collector_helper = $this->collectors[$collector_service_name];

      $storage_key = $collector_helper->getSaveDataKey();
      $temporary_storage_key = $storage_key . '_temporary';

      // Merge what we've just collected for this collector with what we've
      // collected on previous calls to this method.
      $data = $storage->retrieve($temporary_storage_key);

      $data = $collector_helper->mergeComponentData($data, $collector_incremental_data);

      if (isset($final_jobs[$collector_service_name])) {
        // We've done the final job for this collector. Store the data
        // accumulated so far into the permanent file, and clean up the
        // temporary file.
        $storage->store($storage_key, $data);
        $storage->delete($temporary_storage_key);

        // Add the count to the results.
        $collector_count = $collector_helper->getDataCount($data);
        $results[$collector_helper->getReportingKey()] = $collector_count;
      }
      else {
        $storage->store($temporary_storage_key, $data);
      }
    }

    // Now throw a single exception for everything we caught.
    // TODO: Make this a custom exception class which properly holds multiple
    // exceptions.
    if ($exceptions_caught) {
      $message = implode(', ', array_map(fn ($exception) => $exception->getMessage(), $exceptions_caught));
      // TODO: say in which collect and job these were!
      throw new \Exception($message);
    }
  }

  /**
   * Collect data about Drupal components from the current site's codebase.
   *
   * @return array
   *   An array summarizing the collected data. Each key is a label, each value
   *   is a count of that type of item.
   */
  public function collectComponentData() {
    $result = [];

    // Allow each of our declared collectors to perform its work.
    foreach ($this->collectors as $collector_class_name => $collector_helper) {
      // Get the list of jobs.
      $job_list = $collector_helper->getJobList();

      $collector_data = $collector_helper->collect($job_list);

      // Save the data.
      $data_key = $collector_helper->getSaveDataKey();
      assert(!empty($data_key));
      $this->environment->getStorage()->store($data_key, $collector_data);

      // Add the count to the results.
      $count = $collector_helper->getDataCount($collector_data);

      $result[$collector_helper->getReportingKey()] = $count;
    }

    return $result;
  }

}
