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
