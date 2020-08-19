<?php

namespace DrupalCodeBuilder\Generator;

use \DrupalCodeBuilder\Exception\InvalidInputException;
use DrupalCodeBuilder\Generator\Render\ClassAnnotation;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\VariantGeneratorDefinition;
use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for a plugin.
 */
class Plugin extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition($data_type = 'complex'): PropertyDefinition {
    $plugin_data_task = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $services_data_task = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');

    $definition = GeneratorDefinition::createFromGeneratorType('Plugin', 'mutable')
      ->setProperties([
        'plugin_type' => PropertyDefinition::create('string')
          ->setLabel('Plugin type')
          ->setOptionsArray(
            $plugin_data_task->listPluginNamesOptions()
          )

        // TODO: contains the code to support using the plugin folder name.
        // TODO: restore this later, but currently there's no CLI UI for this
        // version so not needed yet.
        //   'XXprocessing' => function(DataItem $component_data) {
        //     // Validate the plugin type, and find it if given a folder rather than
        //     // a type.
        //     $task_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
        //     $plugin_types_data = $task_report_plugins->listPluginData(static::$discoveryType);

        //     // Try to find the intended plugin type.
        //     if (isset($plugin_types_data[$component_data->value])) {
        //       $plugin_data = $plugin_types_data[$component_data->value];
        //     }
        //     else {
        //       // Convert a namespace separator into a directory separator.
        //       $value = str_replace('\\', '/', $value);

        //       $plugin_types_data_by_subdirectory = $task_report_plugins->listPluginDataBySubdirectory();
        //       if (isset($plugin_types_data_by_subdirectory[$value])) {
        //         $plugin_data = $plugin_types_data_by_subdirectory[$value];

        //         // Set the plugin ID in the the property.
        //         $component_data[$property_name] = $plugin_data['type_id'];
        //       }
        //       else {
        //         // Nothing found. Throw exception.
        //         $discovery_type = static::$discoveryType;
        //         throw new InvalidInputException("Plugin type {$value} not found in list of {$discovery_type} plugins.");
        //       }
        //     }

        //     // Set the plugin type data.
        //     // Bit of a cheat, as undeclared data property!
        //     $component_data['plugin_type_data'] = $plugin_data;
        //   },
        // ];

      ])
      ->setVariantMapping($plugin_data_task->getPluginTypesMapping())
      ->setVariants([
        'annotation' => VariantGeneratorDefinition::create()
          ->setLabel('Annotation plugin')
          ->setGenerator('PluginAnnotationDiscovery'),
        'yaml' => VariantGeneratorDefinition::create()
          ->setLabel('YAML plugin')
          ->setGenerator('PluginYamlDiscovery'),
      ]);

    return $definition;
  }

}
