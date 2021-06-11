<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\ReportServiceData.
 */

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Definition\OptionsProviderInterface;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;
use MutableTypedData\Definition\OptionDefinition;

/**
 * Task handler for reporting on service data.
 */
class ReportServiceData extends ReportHookDataFolder implements OptionsProviderInterface, SectionReportInterface {
  use SectionReportSimpleCountTrait;

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      'key' => 'services',
      'label' => 'Services',
      'weight' => 10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    return $this->listServiceNamesOptionsAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    $options = [];
    foreach ($this->listServiceNamesOptionsAll() as $value => $label) {
      $options[$value] = OptionDefinition::create($value, $label);
    }

    return $options;
  }

  /**
   * Get the list of Service data.
   *
   * @return
   *  The processed Service data.
   */
  function listServiceData() {
    $service_data = $this->loadServiceData();

    return $service_data['all'];
  }

  /**
   * Get a list of options of the major services.
   *
   * @return
   *   An array of Service types as options suitable for FormAPI.
   */
  function listServiceNamesOptions() {
    $service_data = $this->loadServiceData();

    $return = [];
    foreach ($service_data['primary'] as $service_id => $service_info) {
      $return[$service_id] = $service_info['label'];
    }

    return $return;
  }

  /**
   * Get a list of options of all the services.
   *
   * @return
   *   An array of Service types as options suitable for FormAPI.
   */
  public function listServiceNamesOptionsAll() {
    $service_data = $this->loadServiceData();

    $return = [];
    foreach ($service_data['all'] as $service_id => $service_info) {
      $return[$service_id] = $service_info['label'];
    }

    return $return;
  }

  /**
   * Get the list of Service types data.
   *
   * @return
   *  The processed Service types data.
   */
  public function listServiceTypeData() {
    if (!isset($this->serviceTypesData)) {
      $this->serviceTypesData = $this->environment->getStorage()->retrieve('service_tag_types');
    }
    return $this->serviceTypesData;
  }

  /**
   * Loads the service data from storage.
   *
   * @return
   *   The data array, as stored by the ServicesCollector.
   */
  protected function loadServiceData() {
    if (!isset($this->serviceData)) {
      $this->serviceData = $this->environment->getStorage()->retrieve('services');
    }

    // Populate the keys, in case analysis crashed.
    $this->serviceData += [
      'primary' => [],
      'all' => [],
    ];

    return $this->serviceData;
  }

}
