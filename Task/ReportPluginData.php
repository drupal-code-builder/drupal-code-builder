<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\ReportPluginData.
 */

namespace DrupalCodeBuilder\Task;

/**
 * Task handler for reporting on hook data.
 *
 * TODO: revisit some of these and clean up names / clean up how many we have.
 * Consider merging into a ReportComponentData Task.
 */
class ReportPluginData extends ReportHookDataFolder {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * Get the list of plugin data.
   *
   * @return
   *  The unserialized contents of the processed plugin data file.
   */
  function listPluginData() {
    // We may come here several times, so cache this.
    // TODO: look into finer-grained caching higher up.
    static $plugin_data;

    if (isset($plugin_data)) {
      return $plugin_data;
    }

    $directory = $this->environment->getHooksDirectory();

    $plugins_file = "$directory/plugins_processed.php";
    if (file_exists($plugins_file)) {
      $plugins_data = unserialize(file_get_contents($plugins_file));
      return $plugins_data;
    }
    // Sanity checks ensure we never get here, but in case they have been
    // skipped, return something that makes sense to the caller.
    return array();
  }

  /**
   * Returns a list of plugin types, keyed by subdirectory.
   *
   * @return
   *  A list of all plugin types that use annotation discovery, keyed by the
   *  subdirectory the plugin files go in, for example, 'Block', 'QueueWorker'.
   */
  public function listPluginDataBySubdirectory() {
    $plugin_types_data = $this->listPluginData();
    $plugin_types_data_by_subdirectory = [];
    foreach ($plugin_types_data as $plugin_id => $plugin_definition) {
      if (!empty($plugin_definition['subdir'])) {
        $subdir = substr($plugin_definition['subdir'], strlen('Plugin/'));

        $plugin_types_data_by_subdirectory[$subdir] = $plugin_definition;
      }
    }
    return $plugin_types_data_by_subdirectory;
  }

  /**
   * Get plugin types as a list of options.
   *
   * @return
   *   An array of plugin types as options suitable for FormAPI.
   */
  function listPluginNamesOptions() {
    $data = $this->listPluginData();

    $return = array();
    foreach ($data as $plugin_type_name => $plugin_type_info) {
      $return[$plugin_type_name] = $plugin_type_info['type_label'];
    }

    return $return;
  }

}
