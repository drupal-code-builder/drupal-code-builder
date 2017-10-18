<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\ReportServiceData.
 */

namespace DrupalCodeBuilder\Task;

/**
 * Task handler for reporting on service data.
 */
class ReportServiceData extends ReportHookDataFolder {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * Get the list of Service data.
   *
   * @return
   *  The processed Service data.
   */
  function listServiceData() {
    // We may come here several times, so cache this.
    // TODO: look into finer-grained caching higher up.
    static $service_data;

    if (isset($service_data)) {
      return $service_data;
    }

    $service_data = $this->environment->getStorage()->retrieve('services');
    return $service_data;
  }

  /**
   * Get Service types as a list of options.
   *
   * @return
   *   An array of Service types as options suitable for FormAPI.
   */
  function listServiceNamesOptions() {
    $data = $this->listServiceData();

    $return = array();
    foreach ($data as $service_id => $service_info) {
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
    $service_types_data = $this->environment->getStorage()->retrieve('service_tag_types');
    return $service_types_data;
  }

}
