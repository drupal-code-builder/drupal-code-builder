<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Task helper for collecting data on entity types.
 */
class EntityTypesCollector extends CollectorBase {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'entity_types';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'entity types';

  /**
   * {@inheritdoc}
   */
  protected $testingIds = [
    'block',
    'node_type',
    'node',
    'user',
    'user_role',
  ];

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
    $entity_types = \Drupal::service('entity_type.manager')->getDefinitions();

    $data = [];

    foreach ($entity_types as $id => $entity_type) {
      $data[$id] = [
        'label' => $entity_type->getLabel()->getUntranslatedString(),
        'group' => $entity_type->getGroup(),
      ];
    }

    return $data;
  }

}
