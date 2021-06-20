<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Definition\OptionsProviderInterface;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;
use MutableTypedData\Definition\OptionDefinition;

/**
 * Task handler for reporting on admin routes.
 *
 * This class is internal, pending a refactoring of the report classes.
 *
 * @internal
 */
class ReportAdminRoutes extends ReportHookDataFolder implements OptionsProviderInterface, SectionReportInterface {
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
      'key' => 'admin_routes',
      'label' => 'Admin routes',
      'weight' => 6,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    return $this->listAdminRoutesOptions();
  }

  /**
   * Get the list of data types data.
   *
   * @return
   *  The processed data types data.
   */
  public function listAdminRoutes() {
    return $this->loadAdminRouteData();
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    $options = [];
    foreach ($this->listAdminRoutesOptions() as $id => $label) {
      $options[$id] = OptionDefinition::create($id, $label);
    }

    return $options;
  }

  /**
   * Get a list of options for data types.
   *
   * @return
   *   An array of data types as options suitable for FormAPI.
   */
  public function listAdminRoutesOptions() {
    $data = $this->loadAdminRouteData();

    $return = [];
    foreach ($data as $route_name => $data_item) {
      $return[$route_name] = $data_item['title'] . ' - ' . $data_item['path'];
    }

    return $return;
  }

  /**
   * Loads the data type data from storage.
   *
   * @return
   *   The data array, as stored by the DataTypesCollector.
   */
  protected function loadAdminRouteData() {
    if (!isset($this->adminRoutesData)) {
      $this->adminRoutesData = $this->environment->getStorage()->retrieve('admin_routes');
    }

    return $this->adminRoutesData;
  }

}
