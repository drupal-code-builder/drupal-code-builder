<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Definition\OptionsProviderInterface;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;

/**
 * Task handler for reporting on field type data.
 */
class ReportFieldTypes extends ReportHookDataFolder implements OptionsProviderInterface, SectionReportInterface {
  use OptionsProviderTrait;
  use SectionReportSimpleCountTrait;

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * The name of the method providing an array of options as $value => $label.
   */
  protected static $optionsMethod = 'listFieldTypesOptions';

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      'key' => 'field_types',
      'label' => 'Field types',
      'weight' => 20,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    return $this->listFieldTypesOptions();
  }

  /**
   * Get the list of field types data.
   *
   * @return
   *  The processed field types data.
   */
  public function listFieldTypes() {
    return $this->loadFieldTypeData();
  }

  /**
   * Get a list of options for field types.
   *
   * @return
   *   An array of field types as options suitable for FormAPI.
   */
  public function listFieldTypesOptions() {
    $field_types_data = $this->loadFieldTypeData();

    $return = [];
    foreach ($field_types_data as $field_type_id => $field_type_info) {
      $return[$field_type_id] = $field_type_info['label'];
    }

    return $return;
  }

  /**
   * Loads the field type data from storage.
   *
   * @return
   *   The data array, as stored by the FieldTypesCollector.
   */
  protected function loadFieldTypeData() {
    if (!isset($this->fieldTypesData)) {
      $this->fieldTypesData = $this->environment->getStorage()->retrieve('field_types');
    }

    return $this->fieldTypesData;
  }

}
