<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Task helper for collecting data on data types.
 */
class DataTypesCollector extends CollectorBase {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'data_types';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'data types';

  /**
   * The names of data types to collect for testing sample data.
   */
  protected $testingDataTypes = [
    'text' => TRUE,
    'boolean' => TRUE,
  ];

  /**
   * Constructs a new helper.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment object.
   */
  public function __construct(
    EnvironmentInterface $environment
  ) {
    $this->environment = $environment;
  }

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    // No point splitting this up into jobs.
    return NULL;
  }

  /**
   * Collect data on data types.
   *
   * @return array
   *   An array keyed by the type ID, where the value is an array with:
   *   - 'type': The type ID.
   *   - 'label': The label.
   */
  public function collect($job_list) {
    // TODO: is there an API for reading this file? Where?
    $definition_file = 'core/config/schema/core.data_types.schema.yml';
    $yml = file_get_contents($definition_file);
    $value = Yaml::parse($yml);

    $data_types = [];
    foreach ($value as $type => $definition_item) {
      // Skip the special types.
      if ($type == 'undefined' || $type == 'ignore') {
        continue;
      }

      // Skip compound types, until we figure out which ones we want.
      if (isset($definition_item['mapping'])) {
        continue;
      }
      if (isset($definition_item['sequence'])) {
        continue;
      }

      // Skip types with a wildcard in the name.
      if (strpos($type, '*') !== FALSE) {
        continue;
      }

      // Skip types that are to do with fields.
      if (substr($type, 0, strlen('field.')) == 'field.') {
        continue;
      }

      // Skip types that don't seem like they should be used for config
      // entities (??).
      if (substr($type, - strlen('settings')) == 'settings') {
        continue;
      }
      if (strpos($type, 'date_format') !== FALSE) {
        continue;
      }

      $data_types[$type] = [
        'type' => $type,
        'label' => $definition_item['label'],
      ];
    }

    ksort($data_types);

    // Filter for testing sample data collection.
    if (!empty($this->environment->sample_data_write)) {
      $data_types = array_intersect_key($data_types, $this->testingDataTypes);
    }

    return $data_types;
  }

}
