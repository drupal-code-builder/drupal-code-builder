<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for YAML plugins.
 */
class PluginYAML extends BaseGenerator {

  use PluginTrait;

  /**
   * The plugin discovery type used by the plugins this generates.
   *
   * @var string
   */
  protected static $discoveryType = 'YamlDiscovery';

  /**
   * {@inheritdoc}
   */
  function __construct($component_data) {
    $plugin_type = $component_data['plugin_type'];

    $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $plugin_types_data = $mb_task_handler_report_plugins->listPluginData();

    // The plugin type has already been validated by the plugin_type property's
    // processing.
    $component_data['plugin_type_data'] = $plugin_types_data[$plugin_type];

    parent::__construct($component_data);
  }

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'plugin_type' => static::getPluginTypePropertyDefinition(),
      'prefix_name' => [
        'internal' => TRUE,
        'format' => 'boolean',
        'default' => TRUE,
      ],
      'plugin_name' => [
        'label' => 'Plugin name',
        'description' => 'The plugin name. A module name prefix is added automatically.',
        'required' => TRUE,
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          if ($component_data['prefix_name']) {
            // YAML plugin names use dots as glue.
            $component_data['plugin_name'] = $component_data['root_component_name'] . '.' . $component_data['plugin_name'];
          }
        },
      ],
      // These are different for each plugin type, so internal for now.
      // When we have dynamic defaults, populate with the default property
      // values, as an array of options?
      'plugin_properties' => [
        'internal' => TRUE,
        'format' => 'array',
        'default' => function($component_data) {
          // Group the plugin properties into those with default values given, and
          // those with empty defaults. We can then put the ones with defaults later,
          // as these are the most likely to be the less frequently used ones.
          $plugin_properties_with_defaults = [];
          $plugin_properties_without_defaults = [];
          $yaml_properties = $component_data['plugin_type_data']['yaml_properties'];
          foreach ($yaml_properties as $property_name => $property_default) {
            if (empty($property_default)) {
              $plugin_properties_without_defaults[$property_name] = $property_default;
            }
            else {
              $plugin_properties_with_defaults[$property_name] = $property_default;
            }
          }
          return $plugin_properties_without_defaults + $plugin_properties_with_defaults;
        }
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $yaml_file_suffix = $this->component_data['plugin_type_data']['yaml_file_suffix'];

    $components = array(
      "%module.{$yaml_file_suffix}.yml" => array(
        'component_type' => 'YMLFile',
        'filename' => "%module.{$yaml_file_suffix}.yml",
      ),
    );

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    $yaml_file_suffix = $this->component_data['plugin_type_data']['yaml_file_suffix'];

    return "%self:%module.{$yaml_file_suffix}.yml";
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $plugin_name = $this->component_data['plugin_name'];

    $yaml_data[$plugin_name] = $this->component_data['plugin_properties'];

    return [
      'plugin' => [
        'role' => 'yaml',
        'content' => $yaml_data,
      ],
    ];
  }

}
