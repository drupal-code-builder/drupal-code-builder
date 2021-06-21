<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Definition\OptionsProviderInterface;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;

/**
 * Task handler for reporting on data type data.
 */
class ReportDataTypes extends ReportHookDataFolder implements OptionsProviderInterface, SectionReportInterface {
  use OptionsProviderTrait;
  use SectionReportSimpleCountTrait;

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * The name of the method providing an array of options as $value => $label.
   */
  protected static $optionsMethod = 'listDataTypesOptions';

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      'key' => 'data_types',
      'label' => 'Data types',
      'weight' => 22,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    return $this->listDataTypesOptions();
  }

  /**
   * Get the list of data types data.
   *
   * @return
   *  The processed data types data.
   */
  public function listDataTypes() {
    return $this->loadDataTypeData();
  }

  /**
   * Get a list of options for data types.
   *
   * @return
   *   An array of data types as options suitable for FormAPI.
   */
  public function listDataTypesOptions() {
    $data = $this->loadDataTypeData();

    $return = [];
    foreach ($data as $id => $data_item) {
      $return[$id] = $data_item['label'];
    }

    return $return;
  }

  /**
   * Loads the data type data from storage.
   *
   * @return
   *   The data array, as stored by the DataTypesCollector.
   */
  protected function loadDataTypeData() {
    if (!isset($this->dataTypesData)) {
      $this->dataTypesData = $this->environment->getStorage()->retrieve('data_types');
    }

    return $this->dataTypesData;
  }

}
