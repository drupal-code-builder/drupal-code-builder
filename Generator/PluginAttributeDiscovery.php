<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use \DrupalCodeBuilder\Exception\InvalidInputException;
use DrupalCodeBuilder\Generator\Render\ClassAnnotation;
use DrupalCodeBuilder\Generator\Render\Docblock;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\VariantGeneratorDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use CaseConverter\CaseString;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for an attribute plugin.
 *
 * This is a variant generator for the Plugin generator, and should not be
 * used directly.
 */
class PluginAttributeDiscovery extends PluginClassDiscovery {

  /**
   * {@inheritdoc}
   */
  protected function getClassAttributes(): ?PhpAttributes {
    $attribute_class = $this->plugin_type_data['plugin_definition_attribute_name'];
    $attribute_variables = $this->plugin_type_data['plugin_properties'];

    $attribute_data = [];
    foreach ($attribute_variables as $attribute_variable => $attribute_variable_info) {
      if ($attribute_variable == 'id') {
        $attribute_data['id'] = $this->component_data->prefixed_plugin_name->value;
        continue;
      }

      if (in_array($attribute_variable, ['label', 'admin_label'])) {
        $attribute_data[$attribute_variable] = PhpAttributes::object(
          // Assume the label will be translatable and therefore have a type.
          $attribute_variable_info['type'],
          $this->component_data->plugin_label->value,
        );
        continue;
      }

      // Skip a deriver property.
      if ($attribute_variable == 'deriver') {
        continue;
      }

      if (str_starts_with($attribute_variable_info['type'], '\\')) {
        $attribute_data[$attribute_variable] = PhpAttributes::object(
          $attribute_variable_info['type'],
          "TODO: replace this with a value",
        );
      }
      elseif ($attribute_variable_info['type'] == 'array') {
        $attribute_data[$attribute_variable] = [
          "TODO: key" => "TODO: replace this with a value",
        ];
      }
      else {
        $attribute_data[$attribute_variable] = "TODO: replace this with a value";
      }
    }

    $attribute = PhpAttributes::class(
      $attribute_class,
      $attribute_data,
    );
    return $attribute;
  }

}
