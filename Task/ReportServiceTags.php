<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Task\Report\SectionReportInterface;

/**
 * Task handler for reporting on service tags.
 */
class ReportServiceTags extends ReportHookDataFolder implements SectionReportInterface {
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
      'key' => 'service_tag_types',
      'label' => 'Service tag types',
      'weight' => 12,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    // TODO: move this to a trait.
    if (!isset($this->data)) {
      $this->data = $this->environment->getStorage()->retrieve('service_tag_types');
    }

    $list = [];
    foreach ($this->data as $tag => $item) {
      $list[$tag] = $item['label'];
    }

    return $list;
  }

}
