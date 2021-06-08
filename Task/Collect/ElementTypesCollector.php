<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Task helper for collecting data on form and render element types.
 */
class ElementTypesCollector extends CollectorBase {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'element_types';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'element types';

  /**
   * {@inheritdoc}
   */
  protected $testingIds = [
    'textfield',
    'textarea',
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
   * Collect data on element types.
   *
   * @return array
   *   An array keyed by the type ID, where the value is an array with:
   *   - 'type': The type ID.
   *   - 'label': The label, which is the same as the type.
   *   - 'form': Whether the element is a form input element.
   */
  public function collect($job_list) {
    $element_types = \Drupal::service('plugin.manager.element_info')->getDefinitions();

    $data = [];
    foreach ($element_types as $id => $definition) {
      // We could use getInfo() on the plugin manager here, but it instantiates
      // each plugin which exhausts memory.
      $form = is_a($definition['class'], \Drupal\Core\Render\Element\FormElementInterface::class, TRUE);
      $data[$id] = [
        'type' => $id,
        'label' => $id,
        'form' => $form,
      ];
    }

    ksort($data);

    return $data;
  }

}
