<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\ReportPluginData.
 */

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Definition\OptionsProviderInterface;
use DrupalCodeBuilder\Definition\VariantMappingProviderInterface;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;
use MutableTypedData\Definition\OptionDefinition;

/**
 * Task handler for reporting on hook data.
 *
 * TODO: revisit some of these and clean up names / clean up how many we have.
 * Consider merging into a ReportComponentData Task.
 */
class ReportPluginData extends ReportHookDataFolder
  implements OptionsProviderInterface, VariantMappingProviderInterface, SectionReportInterface {
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
      'key' => 'plugins',
      'label' => 'Plugin types',
      'weight' => 5,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    return $this->listPluginNamesOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    $options = [];
    foreach ($this->listPluginNamesOptions() as $value => $label) {
      $options[$value] = OptionDefinition::create($value, $label);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariantMapping(): array {
    return $this->getPluginTypesMapping();
  }

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

    $return = [];
    foreach ($data as $plugin_type_name => $plugin_type_info) {
      $return[$plugin_type_name] = $plugin_type_info['type_label'];
    }

    return $return;
  }

  public function getPluginTypesMapping() :array {
    $mapping = [];

    $data = $this->listPluginData();

    // TODO: move all this to analysis!
    $types = [
      'Drupal\\Core\\Plugin\\Discovery\\AnnotatedClassDiscovery' => 'annotation',
      'Drupal\\Core\\Plugin\\Discovery\\YamlDiscovery' => 'yaml',
      'Drupal\Core\Config\Schema\ConfigSchemaDiscovery' => 'yaml', // ????
    ];

    foreach ($data as $plugin_type_name => $plugin_type_info) {
      // Quick hack: default to 'yaml' variant, as so far there's only
      // migrations that we don't handle, which are YAML.
      // TODO: when this is done in analysis, types we don't support should be
      // filtered out.
      $mapping[$plugin_type_name] = $types[$plugin_type_info['discovery']] ?? 'yaml';
    }

    // Special case for validation constraint plugins.
    $mapping['validation.constraint'] = 'validation.constraint';

    return $mapping;
  }

}
