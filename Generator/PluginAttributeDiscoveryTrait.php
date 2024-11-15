<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\Render\PhpAttributes;

/**
 * Trait for an attribute plugin.
 */
trait PluginAttributeDiscoveryTrait {

  /**
   * {@inheritdoc}
   */
  protected function getClassAttributes(): ?PhpAttributes {
    $attribute_class = $this->plugin_type_data['plugin_definition_attribute_name'];
    $attribute_variables = $this->plugin_type_data['plugin_properties'];

    // Special case: attribute that's just the plugin ID.
    if (!empty($this->plugin_type_data['annotation_id_only'])) {
      $attribute = PhpAttributes::class(
        $attribute_class,
        [
          $this->component_data->prefixed_plugin_name->value
        ],
      );
      $attribute->forceInline();

      return $attribute;
    }

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

      $todo_string = empty($attribute_variable_info['optional'])
        ? 'TODO: replace this with a value'
        :  'OPTIONAL: replace this with a value';

      if (str_starts_with($attribute_variable_info['type'], '\\')) {
        // Object type.
        $attribute_data[$attribute_variable] = PhpAttributes::object(
          $attribute_variable_info['type'],
          // TODO: This will fail if the object's parameter is not a single
          // string! But so far it's usually always TranslatableMarkup.
          $todo_string,
        );
      }
      else {
        // Scalar type or array.
        $attribute_sample_value = match($attribute_variable_info['type']) {
          'int' => '42',
          'float' => '42.0',
          'bool' => 'FALSE',
          'string' => $todo_string,
          'array' => [
            "TODO: key" => $todo_string,
          ],
          default => $todo_string,
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
