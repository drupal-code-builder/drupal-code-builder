<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Collect.
 */

namespace DrupalCodeBuilder\Task;

/**
 * Task handler for collecting and processing definitions for Drupal components.
 *
 * This will do different things depending on the core Drupal version:
 *  - on D5/6, this downloads documentation files from drupal.org containing
 *    definitions of hooks.
 *  - on D7, this collects hook documentation files from the current site.
 *  - on D8, this collects data about plugins as well as hooks.
 */
class Collect extends Base {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'data_directory_exists';

  /**
   * The short names of classes in this namespace to use as collectors.
   *
   * @var string[]
   */
  protected $collectorClassNames = [
    'HooksCollector',
  ];

  /**
   *  Helper objects.
   *
   * @var array
   */
  protected $helpers = [];

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

    foreach ($this->collectorClassNames as $collector_class_name) {
      // Get the list of jobs from each collector.
      $collector_helper = $this->getHelper($collector_class_name);
      $collector_job_list = $collector_helper->getJobList();

      if (is_null($collector_job_list)) {
        // Collector doesn't support jobs: create just a single job for it.
        $job_list[] = [
          'collector' => $collector_class_name,
          'process_label' => $collector_helper->getReportingKey(),
          // A singleton job is by definition the last one.
          // Simpler to do this and have the analysis waste time trying to load
          // a temporary file than add special handling for singleton jobs.
          'last' => TRUE,
        ];
      }
      else {
        array_walk($collector_job_list, function(&$item) use ($collector_class_name) {
          $item['collector'] = $collector_class_name;
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
    // Populate an array of incremental data that we collect for the given job
    // list, so array_merge() works.
    $incremental_data = array_fill_keys($this->collectorClassNames, []);

    // Keep track of any jobs which are the last for their collector, so we
    // know to write to the real storage file rather than temporary.
    $final_jobs = [];

    // Group the jobs by collector.
    $grouped_jobs = [];
    foreach ($job_list as $job) {
      $collector_class_name = $job['collector'];
      $grouped_jobs[$collector_class_name][] = $job;
    }

    // Pass each set of jobs to its respective collective.
    foreach ($grouped_jobs as $collector_class_name => $jobs) {
      $collector_helper = $this->getHelper($collector_class_name);

      $incremental_data[$collector_class_name] = $collector_helper->collect($jobs);

      $last_job = end($jobs);
      if (!empty($last_job['last'])) {
        $final_jobs[$collector_class_name] = TRUE;
      }
    }

    // Save the data for each collector.
    $storage = $this->environment->getStorage();
    foreach ($incremental_data as $collector_name => $collector_incremental_data) {
      // We have a key for every collector in $incremental_data, but have not
      // necessarily run jobs for that collector in this batch. Skip if empty.
      if (empty($collector_incremental_data)) {
        continue;
      }

      $collector_helper = $this->getHelper($collector_name);

      $storage_key = $collector_helper->getSaveDataKey();
      $temporary_storage_key = $storage_key . '_temporary';

      // Merge what we've just collected for this collector with what we've
      // collected on previous calls to this method.
      $data = $storage->retrieve($temporary_storage_key);

      $data = $collector_helper->mergeComponentData($data, $collector_incremental_data);

      if (isset($final_jobs[$collector_name])) {
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
    foreach ($this->collectorClassNames as $collector_class_name) {
      $collector_helper = $this->getHelper($collector_class_name);

      // Get the list of jobs.
      // (In 3.3.x, this list will be exposed to the API, so UIs can run the
      // analysis in batches.)
      $job_list = $collector_helper->getJobList();

      $collector_data = $collector_helper->collect($job_list);

      // Save the data.
      $this->environment->getStorage()->store($collector_helper->getSaveDataKey(), $collector_data);

      // Add the count to the results.
      $count = $collector_helper->getDataCount($collector_data);

      $result[$collector_helper->getReportingKey()] = $count;
    }

    return $result;
  }

  /**
   * Returns the helper for the given short class name.
   *
   * @param $class
   *   The short class name.
   *
   * @return
   *   The helper object.
   */
  protected function getHelper($class) {
    if (!isset($this->helpers[$class])) {
      // On D7 and older, there is only the HooksCollector helper, which has
      // a version number suffix.
      $version  = \DrupalCodeBuilder\Factory::getEnvironment()->getCoreMajorVersion();
      $qualified_class = '\DrupalCodeBuilder\Task\Collect\\' . $class . $version;
      $helper = new $qualified_class($this->environment);

      $this->helpers[$class] = $helper;
    }

    return $this->helpers[$class];
  }

}
