<?php

namespace DrupalCodeBuilder\Task;

/**
 * Trait for section report tasks whose data is flat.
 *
 * Implements \DrupalCodeBuilder\Task\Report\SectionReportInterface::count().
 *
 * This is in the Task namespace as that's where the classes that use it are;
 * move it to Task\Report when the task helpers become internal in 5.0.0.
 *
 * @internal
 */
trait SectionReportSimpleCountTrait {

  /**
   * Helper for \DrupalCodeBuilder\Task\Report\SectionReportInterface.
   */
  public function getCount(): int {
    return count($this->getDataSummary());
  }

}
