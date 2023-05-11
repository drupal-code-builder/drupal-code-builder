<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Attribute\InjectImplementations;
use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;

/**
 * Task handler for reporting a summary of all stored analysis data.
 *
 * This uses a service collector pattern in the ContainerBuilder to get all
 * services which implement
 * \DrupalCodeBuilder\Task\Report\SectionReportInterface.
 *
 * TODO: change the base class; this only extends from ReportHookDataFolder to
 * get the lastUpdatedDate() method.
 */
class ReportSummary extends ReportHookDataFolder {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * Array of report helper services
   *
   * @var array
   */
  protected $helperServices = [];

  /**
   * Sets the report helper services.
   *
   * Called by the container on instantiation.
   *
   * @param array $helper_services
   *  An array of section report tasks. These are all the
   *  services which implement
   *  \DrupalCodeBuilder\Task\Report\SectionReportInterface.
   *
   * @see \DrupalCodeBuilder\DependencyInjection\ContainerBuilder
   */
  #[InjectImplementations(\DrupalCodeBuilder\Task\Report\SectionReportInterface::class)]
  public function setReportHelpers(array $helper_services) {
    $this->helperServices = $helper_services;
  }

  /**
   * Returns a listing of all stored data, with counts.
   *
   * @return
   *   An array whose keys are short identifiers for the types of data, where
   *   each value is itself an array with:
   *   - 'label': A title case label for the type of data, e.g. 'Hooks'.
   *   - 'count': The number of stored definitions of this type.
   *   - 'list': A list of all the items. This is in the same format as Drupal
   *     FormAPI options, i.e. either:
   *     - an array of machine keys and label values.
   *     - a nested array where keys are group labels, and values are keys and
   *       labels as in the non-nested format.
   */
  public function listStoredData() {
    $return = [];

    // Get data from each report section helper.
    foreach ($this->helperServices as $section_report) {
      $section_info = $section_report->getInfo();

      $return[$section_info['key']] = [
        'label' => $section_info['label'],
        'list' => $section_report->getDataSummary(),
        'count' => $section_report->getCount(),
        'weight' => $section_info['weight'],
      ];
    }

    uasort($return, function ($a, $b) {
      return $a['weight'] <=> $b['weight'];
    });

    return $return;
  }

}
