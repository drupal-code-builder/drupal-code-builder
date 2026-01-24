<?php

namespace DrupalCodeBuilder\Task\Collect;

/**
 * Collects metadata such as the time of the analysis run.
 *
 * This is run after all other collectors, so it's only updated if nothing went
 * wrong.
 *
 * @see \DrupalCodeBuilder\Task\Collect::setCollectors()
 */
class MetadataCollector extends CollectorBase {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'metadata';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'metadata';

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    return NULL;
  }

  /**
   * Gets metadata.
   *
   * @return array
   *   An array containing:
   *   - 'timestamp': The time of the analysis.
   */
  public function collect($job_list) {
    return [
      'timestamp' => time(),
    ];
  }

}
