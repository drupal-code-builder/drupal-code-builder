<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\ReportHookData.
 */

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Definition\OptionDefinition;
use MutableTypedData\Definition\OptionSetDefininitionInterface;

/**
 * Task handler for reporting on hook groups.
 */
class ReportHookGroups extends ReportHookDataFolder implements OptionSetDefininitionInterface {

  /**
   * Constructor.
   */
  function __construct(
    protected ReportHookData $reportHookData,
  ) {
  }


  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    $data = $this->reportHookData->listHookData();

    $options = [];
    foreach (array_keys($data) as $group) {
      $options[$group] = OptionDefinition::create(
        $group,
        $group,
        '',
      );
    }
    return $options;
  }

}