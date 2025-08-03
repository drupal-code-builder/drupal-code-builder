<?php

namespace DrupalCodeBuilder\Task\Collect;

use CaseConverter\CaseString;
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
   *   - 'interface': (optional) If detected, the interface for the entity type
   *     class, with the initial backslash.
   */
  public function collect($job_list) {
    $entity_types = \Drupal::service('entity_type.manager')->getDefinitions();

    $data = [];

    foreach ($entity_types as $id => $entity_type) {
      $label = $entity_type->getLabel();
      if (is_object($label)) {
        $label = $label->getUntranslatedString();
      }

      $data[$id] = [
        'label' => $label,
        'group' => $entity_type->getGroup(),
      ];

      // Look for an interface that the entity class implements which starts
      // with the name of the entity type ID (with the appropriate case change).
      $pascal_entity_type_id = CaseString::snake($id)->pascal();
      $entity_class = $entity_type->getClass();
      $interfaces = class_implements($entity_class);
      if ($matching_interfaces = array_filter($interfaces, fn ($interface) => str_contains($interface, $pascal_entity_type_id))) {
        // Assume we only get one.
        $entity_interface = reset($matching_interfaces);
        // PHP doesn't provide the initial backslash.
        $data[$id]['interface'] = '\\' . $entity_interface;
      }
    }

    return $data;
  }

}
