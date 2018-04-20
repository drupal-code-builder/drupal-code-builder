<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Exception\InvalidInputException;
use CaseConverter\CaseString;

/**
 * Provides common methods for plugin generators.
 *
 * Expects $this->discoveryType to be defined.
 */
trait PluginTrait {

  /**
   * Provides the property definition for the plugin type property.
   *
   * @return array
   *   A property definition array.
   */
  protected static function getPluginTypePropertyDefinition() {
    return [
      'label' => 'Plugin type',
      'description' => "The identifier of the plugin type. This can be either the manager service name with the 'plugin.manager.' prefix removed, " .
        ' or the subdirectory name.',
      'required' => TRUE,
      'options' => function(&$property_info) {
        $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');

        $options = $mb_task_handler_report_plugins->listPluginNamesOptions(static::$discoveryType);

        return $options;
      },
      'processing' => function($value, &$component_data, $property_name, &$property_info) {
        // Validate the plugin type, and find it if given a folder rather than
        // a type.
        $task_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
        $plugin_types_data = $task_report_plugins->listPluginData(static::$discoveryType);

        // Try to find the intended plugin type.
        if (isset($plugin_types_data[$value])) {
          $plugin_data = $plugin_types_data[$value];
        }
        else {
          // Convert a namespace separator into a directory separator.
          $value = str_replace('\\', '/', $value);

          $plugin_types_data_by_subdirectory = $task_report_plugins->listPluginDataBySubdirectory();
          if (isset($plugin_types_data_by_subdirectory[$value])) {
            $plugin_data = $plugin_types_data_by_subdirectory[$value];

            // Set the plugin ID in the the property.
            $component_data[$property_name] = $plugin_data['type_id'];
          }
          else {
            // Nothing found. Throw exception.
            $discovery_type = static::$discoveryType;
            throw new InvalidInputException("Plugin type {$value} not found in list of {$discovery_type} plugins.");
          }
        }

        // Set the plugin type data.
        // Bit of a cheat, as undeclared data property!
        $component_data['plugin_type_data'] = $plugin_data;

        // Set the relative qualified class name.
        // The full class name will be of the form:
        //  \Drupal\{MODULE}\Plugin\{PLUGINTYPE}\{PLUGINNAME}
        // where PLUGINNAME has any derivative prefix stripped.
        if (strpos($component_data['plugin_name'], ':') === FALSE) {
          $plugin_id = $component_data['plugin_name'];
        }
        else {
          list (, $plugin_id) = explode(':', $component_data['plugin_name']);
        }
        $plugin_id_pascal = CaseString::snake($plugin_id)->pascal();

        $component_data['plugin_name'];
        $plugin_id_without_prefix = $component_data['plugin_name'];

        $component_data['relative_class_name'] = array_merge(
          // Plugin subdirectory.
          self::pathToNamespacePieces($plugin_data['subdir']),
          // Plugin ID.
          [ $plugin_id_pascal ]
        );
      },
    ];
  }

  /**
   * TODO: is there a core function for this?
   */
  static function pathToNamespacePieces($path) {
    return explode('/', $path);
  }

}
