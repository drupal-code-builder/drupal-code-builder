<?php

namespace DrupalCodeBuilder\Task;

/**
 * Task handler for reporting on data type data.
 */
class ReportDataTypes extends ReportHookDataFolder {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

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
