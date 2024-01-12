<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Task helper for collecting data on field types.
 */
class FieldTypesCollector extends CollectorBase  {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'field_types';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'field types';

  /**
   * The names of field types to collect for testing sample data.
   */
  protected $testingIds = [
    'text',
    'boolean',
    'string',
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
   * Gets definitions of field types.
   *
   * @return array
   *   An array whose keys are the field types, and whose values are arrays
   *   containing:
   *   - 'type': The field type, that is, the field type plugin ID.
   *   - 'label': The field type label.
   *   - 'description': The field type description.
   *   - 'default_widget': The default widget plugin ID.
   *   - 'default_formatter': The default formatter plugin ID.
   */
  public function collect($job_list) {
    $plugin_manager = \Drupal::service('plugin.manager.field.field_type');
    $plugin_definitions = $plugin_manager->getDefinitions();

    $field_types_data = [];

    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      // Some field types brokenly don't define a description.
      if (isset($plugin_definition['description'])) {
        // Babysit field type descriptions which have gone crazy in 10.2.
        if (is_array($plugin_definition['description'])) {
          $description = (string) $plugin_definition['description'][0];
        }
        else {
          $description = (string) $plugin_definition['description'];
        }
      }
      else {
        $description = (string) $plugin_definition['label'];
      }

      $field_types_data[$plugin_id] = [
        'type' => $plugin_id,
        // Labels and descriptions need to be stringified from
        // TranslatableMarkup.
        'label' => (string) $plugin_definition['label'],
        'description' => $description,
        // Some of the weirder plugins don't have these.
        'default_widget' => $plugin_definition['default_widget'] ?? '',
        'default_formatter' => $plugin_definition['default_formatter'] ?? '',
      ];
    }

    uasort($field_types_data, function($a, $b) {
      return strcmp($a['label'], $b['label']);
    });

    return $field_types_data;
  }

}
