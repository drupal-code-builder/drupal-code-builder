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
   *  The unserialized contents of the processed Service data file.
   */
  function listServiceData() {
    // We may come here several times, so cache this.
    // TODO: look into finer-grained caching higher up.
    static $service_data;

    if (isset($service_data)) {
      return $service_data;
    }

    $directory = $this->environment->getHooksDirectory();

    $services_file = "$directory/services_processed.php";
    if (file_exists($services_file)) {
      $service_data = unserialize(file_get_contents($services_file));
      return $service_data;
    }
    // Sanity checks ensure we never get here, but in case they have been
    // skipped, return something that makes sense to the caller.
    return array();
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

}
