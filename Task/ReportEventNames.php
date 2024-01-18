<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Definition\OptionsProviderInterface;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;

/**
 * Task handler for reporting on event names.
 */
class ReportEventNames extends ReportHookDataFolder implements OptionsProviderInterface, SectionReportInterface {
  use OptionsProviderTrait;
  use SectionReportSimpleCountTrait;

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'event_names';

  /**
   * The name of the method providing an array of options as $value => $label.
   */
  protected static $optionsMethod = 'listEventNamesOptions';

  /**
   * The data.
   */
  protected $data;

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      'key' => 'event_names',
      'label' => 'Event names',
      'weight' => 22,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    return $this->loadData();
  }

  /**
   * Get the list of data types data.
   *
   * @return
   *  The processed data types data.
   */
  public function listEventNames() {
    return $this->loadData();
  }

  /**
   * Get a list of options for data types.
   *
   * @return
   *   An array of data types as options suitable for FormAPI.
   */
  public function listEventNamesOptions() {
    $data = $this->loadData();

    $return = [];
    foreach ($data as $id => $data_item) {
      $return[$id] = $id;
    }

    return $return;
  }

  /**
   * Loads the data type data from storage.
   *
   * @return
   *   The data array, as stored by the DataTypesCollector.
   */
  protected function loadData() {
    if (!isset($this->data)) {
      $this->data = $this->environment->getStorage()->retrieve($this->saveDataKey);
    }

    return $this->data;
  }

}
