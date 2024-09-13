<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Generator\Render\ClassAnnotation;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\VariantGeneratorDefinition;
use CaseConverter\CaseString;
use CaseConverter\StringAssembler;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for an annotation plugin.
 *
 * This is a variant generator for the Plugin generator, and should not be
 * used directly.
 */
class PluginYamlDiscovery extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $plugin_data_task = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');

    $definition->addProperties([
      'plugin_name' => PropertyDefinition::create('string')
        ->setLabel('Plugin ID')
        ->setRequired(TRUE)
        ->setValidators('yaml_plugin_name'),
      'deriver' => PropertyDefinition::create('boolean')
        ->setLabel('Use deriver')
        ->setDescription("Adds a deriver class to dynamically derive plugins from a template."),
      'deriver_plain_class_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setCallable(function (DataItem $component_data) {
            $plugin_data = $component_data->getParent();

            // Convert the plugin type ID to pascal case. It may contain dots
            // and other non-snake case characters that need special handling.
            $plugin_type_id = $plugin_data->plugin_type_data->value['type_id'];
            $plugin_type_id_pieces = preg_split('@[^[:alpha:]]@', $plugin_type_id);
            $pascal_plugin_type_id = (new StringAssembler($plugin_type_id_pieces))->pascal();

            return
              CaseString::snake($plugin_data->plugin_name->value)->pascal() .
              $pascal_plugin_type_id .
              'Deriver';
          })
        ),
      'prefix_name' => PropertyDefinition::create('boolean')
        ->setInternal(TRUE)
        ->setLiteralDefault(TRUE),
      'prefixed_plugin_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setCallable(function (DataItem $component_data) {
              // YAML plugin names use dots as glue.
              $component_data->value =
                $component_data->getParent()->root_component_name->value
                . '.' .
                $component_data->getParent()->plugin_name->value;
            })
            ->setDependencies('..:plugin_name')
        ),
      'plugin_type_data' => PropertyDefinition::create('mapping')
        ->setInternal(TRUE),
      // These are different for each plugin type, so internal for now.
      // When we have dynamic defaults, populate with the default property
      // values, as an array of options?
      'plugin_properties' => PropertyDefinition::create('mapping')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setCallable([static::class, 'defaultPluginProperties'])
            ->setDependencies('..:plugin_name')
        ),

    ]);
  }

  /**
   * {@inheritdoc}
   */
  function __construct($component_data) {
    $plugin_type = $component_data['plugin_type'];

    $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $plugin_types_data = $mb_task_handler_report_plugins->listPluginData();

    // The plugin type has already been validated by the plugin_type property's
    // processing.
    $component_data->plugin_type_data->set($plugin_types_data[$plugin_type]);

    parent::__construct($component_data);
  }

  /**
   * Default property value callback for 'plugin_properties'.
   */
  public static function defaultPluginProperties($data_item) {
    // Bypass the plugin type data if there's a deriver, as that is the only
    // property needed in that case.
    if (!empty($data_item->getParent()->deriver->value)) {
      $properties['deriver'] = '\Drupal\%module\Plugin\Derivative\\' . $data_item->getParent()->deriver_plain_class_name->value;

      return $properties;
    }

    // Group the plugin properties into those with default values given, and
    // those with empty defaults. We can then put the ones with defaults later,
    // as these are the most likely to be the less frequently used ones.
    $plugin_properties_with_defaults = [];
    $plugin_properties_without_defaults = [];
    $yaml_properties = $data_item->getParent()->plugin_type_data->value['yaml_properties'];
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

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $yaml_file_suffix = $this->component_data['plugin_type_data']['yaml_file_suffix'];

    $components = [
      "%module.{$yaml_file_suffix}.yml" => [
        'component_type' => 'YMLFile',
        'filename' => "%module.{$yaml_file_suffix}.yml",
      ],
    ];

    if (!empty($this->component_data->deriver->value)) {
      $components['deriver'] = [
        'component_type' => 'PHPClassFile',
        'class_docblock_lines' => [
          'Plugin deriver for ' . $this->component_data->plugin_name->value . '.',
        ],
        'plain_class_name' => $this->component_data->deriver_plain_class_name->value,
        'relative_namespace' => 'Plugin\Derivative',
        'parent_class_name' => '\Drupal\Component\Plugin\Derivative\DeriverBase',
        'interfaces' => [
          '\Drupal\Core\Plugin\Discovery\ContainerDeriverInterface',
        ],
      ];

      $components['getDerivativeDefinitions'] = [
        'component_type' => 'PHPFunction',
        'function_name' => 'getDerivativeDefinitions',
        'containing_component' => '%requester:deriver',
        'docblock_inherit' => TRUE,
        'parameters' => [
          0 => [
            'name' => 'base_plugin_definition',
          ],
        ],
      ];
    }

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
  public function getContents(): array {
    if ($this->component_data->prefix_name->value) {
      $plugin_name = $this->component_data->prefixed_plugin_name->value;
    }
    else {
      $plugin_name = $this->component_data->plugin_name->value;
    }

    $yaml_data[$plugin_name] = $this->component_data['plugin_properties'];

    return $yaml_data;
  }

}
