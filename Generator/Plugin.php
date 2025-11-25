<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\VariantGeneratorDefinition;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\OptionsSortOrder;

/**
 * Generator for a plugin.
 */
class Plugin extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected static $dataType = 'mutable';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    // We still need this because ComponentCollector gets a standalone data
    // item for this generator.
    // TODO: figure this out!
    $plugin_data_task = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $services_data_task = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');

    $definition->setProperties([
        'plugin_type' => PropertyDefinition::create('string')
          ->setLabel('Plugin type')
          ->setOptionSetDefinition($plugin_data_task)
          ->setOptionsSorting(OptionsSortOrder::Label)


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
      ->setVariantMappingProvider($plugin_data_task)
      ->setVariants([
        'annotation' => VariantGeneratorDefinition::create()
          ->setLabel('Annotation plugin')
          ->setGenerator('PluginAnnotationDiscovery'),
        'attribute' => VariantGeneratorDefinition::create()
          ->setLabel('Attribute plugin')
          ->setGenerator('PluginAttributeDiscovery'),
        'yaml' => VariantGeneratorDefinition::create()
          ->setLabel('YAML plugin')
          ->setGenerator('PluginYamlDiscovery'),
        // Special cases. Note that these must be set up in ReportPluginData.
        'element_info' => VariantGeneratorDefinition::create()
          ->setLabel('Validaton constraint')
          ->setGenerator('PluginRenderElement'),
        'validation.constraint' => VariantGeneratorDefinition::create()
          ->setLabel('Validaton constraint')
          ->setGenerator('PluginValidationConstraint'),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getDifferentiatedLabelSuffix(DataItem $data): ?string {
    $label = [];
    $label[] = $data->plugin_type->value;

    if ($data->hasProperty('plugin_label')) {
      $label[] = $data->plugin_label->value;
    }
    else {
      $label[] = $data->plugin_name->value;
    }

    return implode(' - ', array_filter($label));
  }

}
