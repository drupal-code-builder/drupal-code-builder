<?php

namespace DrupalCodeBuilder\Task\Report;

/**
 * Interface for reports on a single section of analysis data.
 *
 * @internal
 */
interface SectionReportInterface {

  /**
   * Gets the info for the section.
   *
   * @return array
   *   An array containing:
   *    - 'key': The key used for storage.
   *    - 'label': The human readable label.
   *    - 'weight': The weight to sort this section among other sections. Lower
   *      values are lighter and come first.
   */
  public function getInfo(): array;

  /**
   * Gets the data for this report section.
   *
   * @return array
   *   A list of all the items. This is in the same format as Drupal FormAPI
   *   options, i.e. either:
   *    - an array of machine keys and label values.
   *    - a nested array where keys are group labels, and values are keys and
   *      labels as in the non-nested format.
   */
  public function getDataSummary(): array;

  /**
   * Gets the count of values for this section.
   *
   * @return int
   *   The number of values.
   */
  public function getCount(): int;

}
