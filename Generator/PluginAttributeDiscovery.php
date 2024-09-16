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
    $attribute_comments = [];
    foreach ($attribute_variables as $attribute_variable => $attribute_variable_info) {
      if ($attribute_variable == 'id') {
        $attribute_data['id'] = $this->component_data->prefixed_plugin_name->value;
        continue;
      }

      if ($attribute_variable == $this->plugin_type_data['plugin_label_property']) {
        $attribute_data[$attribute_variable] = PhpAttributes::object(
          // Assume the label will be translatable and therefore have a type.
          $attribute_variable_info['type'],
          $this->component_data->plugin_label->value,
        );
        continue;
      }

      if ($attribute_variable == 'deriver') {
        if ($this->component_data->deriver->value) {
          $attribute_data['deriver'] = '\Drupal\%module\Plugin\Derivative\\' . $this->component_data->deriver_plain_class_name->value . '::class';
        }
        else {
          // Skip a deriver property if not using a deriver.
        }

        continue;
      }

      if (str_starts_with($attribute_variable_info['type'], '\\')) {
        // Object type.
        $attribute_data[$attribute_variable] = PhpAttributes::object(
          $attribute_variable_info['type'],
          // TODO: This will fail if the object's parameter is not a single
          // string! But so far it's usually always TranslatableMarkup.
          "TODO: replace this with a value",
        );
      }
      else {
        // Scalar type or array.
        $attribute_sample_value = match($attribute_variable_info['type']) {
          'int' => '42',
          'float' => '42.0',
          'bool' => 'FALSE',
          'string' => "TODO: replace this with a value",
          'array' => [
            "TODO: key" => "TODO: replace this with a value",
          ],
          default => "TODO: replace this with a value",
        };

        $attribute_data[$attribute_variable] = $attribute_sample_value;
      }

      if (isset($attribute_variable_info['description'])) {
        $attribute_comments[$attribute_variable] = $attribute_variable_info['description'];
      }
    }

    $attribute = PhpAttributes::class(
      $attribute_class,
      $attribute_data,
      $attribute_comments,
    );
    return $attribute;
  }

}
