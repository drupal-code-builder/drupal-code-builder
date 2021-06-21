<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Definition\OptionsProviderInterface;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;

/**
 * Task handler for reporting on render element types.
 */
class ReportElementTypes extends ReportHookDataFolder implements OptionsProviderInterface, SectionReportInterface {
  use OptionsProviderTrait;
  use SectionReportSimpleCountTrait;

  protected $data;

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * The name of the method providing an array of options as $value => $label.
   */
  protected static $optionsMethod = 'getDataSummary';

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      'key' => 'element_types',
      'label' => 'Element types',
      'weight' => 20,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    // TODO: move this to a trait.
    if (!isset($this->data)) {
      $this->data = $this->environment->getStorage()->retrieve($this->getInfo()['key']);
    }

    $list = [];
    foreach ($this->data as $id => $item) {
      $list[$id] = $item['label'];
    }

    return $list;
  }

}
