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
   * @param $discovery_type
   *   (optional) The short name of a discovery class to limit the plugin list
   *   by. Possible values include:
   *    - AnnotatedClassDiscovery
   *    - YamlDiscovery
   *
   * @return
   *  The processed plugin data.
   *
   * @see \DrupalCodeBuilder\Task\Collect8::gatherPluginTypeInfo()
   */
  function listPluginData($discovery_type = NULL) {
    // We may come here several times, so cache this.
    // TODO: look into finer-grained caching higher up.
    static $cache;

    if (isset($cache[$discovery_type])) {
      return $cache[$discovery_type];
    }

    $plugin_data = $this->environment->getStorage()->retrieve('plugins');

    // Filter the plugins by the discovery type.
    if ($discovery_type) {
      $plugin_data = array_filter($plugin_data, function($item) use ($discovery_type) {
        $discovery_pieces = explode('\\', $item['discovery']);
        $discovery_short_name = array_pop($discovery_pieces);

        return ($discovery_short_name == $discovery_type);
      });
    }

    $cache[$discovery_type] = $plugin_data;

    return $plugin_data;
  }

  /**
   * Returns a list of annotated plugin types, keyed by subdirectory.
   *
   * @return
   *  A list of all plugin types that use annotation discovery, keyed by the
   *  subdirectory the plugin files go in, for example, 'Block', 'QueueWorker'.
   */
  public function listPluginDataBySubdirectory() {
    $plugin_types_data = $this->listPluginData('AnnotatedClassDiscovery');
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
   * @param $discovery_type
   *   (optional) The short name of a discovery class to limit the plugin list
   *
   * @return
   *   An array of plugin types as options suitable for FormAPI.
   */
  function listPluginNamesOptions($discovery_type = NULL) {
    $data = $this->listPluginData($discovery_type);

    $return = array();
    foreach ($data as $plugin_type_name => $plugin_type_info) {
      $return[$plugin_type_name] = $plugin_type_info['type_label'];
    }

    return $return;
  }

}
