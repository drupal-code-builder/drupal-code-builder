<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Task\Report\SectionReportInterface;

/**
 * Task handler for reporting on entity types.
 */
class ReportEntityTypes extends ReportHookDataFolder implements SectionReportInterface {
  use SectionReportSimpleCountTrait;

  protected $data;

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      'key' => 'entity_types',
      'label' => 'Entity types',
      'weight' => 30,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    // TODO: move this to a trait.
    if (!isset($this->data)) {
      $this->data = $this->environment->getStorage()->retrieve('entity_types');
    }

    $list = [];
    foreach ($this->data as $id => $item) {
      $list[$id] = $item['label'];
    }

    return $list;
  }

}
