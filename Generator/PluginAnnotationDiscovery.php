<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use \DrupalCodeBuilder\Exception\InvalidInputException;
use DrupalCodeBuilder\Generator\Render\ClassAnnotation;
use DrupalCodeBuilder\Generator\Render\Docblock;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\VariantGeneratorDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use CaseConverter\CaseString;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for an annotation plugin.
 *
 * This is a variant generator for the Plugin generator, and should not be
 * used directly.
 */
class PluginAnnotationDiscovery extends PluginClassDiscovery {

  /**
   * Procudes the docblock for the class.
   */
  protected function getClassDocBlock(): DocBlock {
    $docblock = parent::getClassDocBlock();

    // Do not include the annotation if this plugin is a class override.
    if (!empty($this->component_data['replace_parent_plugin'])) {
      return $docblock;
    }

    $docblock->addAnnotation($this->classAnnotation());

    return $docblock;
  }

  /**
   * Produces the plugin class annotation.
   *
   * @return \DrupalCodeBuilder\Generator\Render\ClassAnnotation
   *   A class annotation object.
   */
  function classAnnotation(): ClassAnnotation {
    $annotation_class_path = explode('\\', $this->plugin_type_data['plugin_definition_annotation_name']);
    $annotation_class = array_pop($annotation_class_path);

    // Special case: annotation that's just the plugin ID.
    if (!empty($this->plugin_type_data['annotation_id_only'])) {
      $annotation = ClassAnnotation::{$annotation_class}($this->component_data['prefixed_plugin_name']);

      return $annotation;
    }

    $annotation_variables = $this->plugin_type_data['plugin_properties'];
    // dump($annotation_variables);

    $annotation_data = [];
    foreach ($annotation_variables as $annotation_variable => $annotation_variable_info) {
      if ($annotation_variable == 'id') {
        // ARGH l
        // CRASH
        // lazy defaults not working with array acess thought I'd fuckkign fixed it!
        $annotation_data['id'] = $this->component_data['prefixed_plugin_name'];
        continue;
      }

      if ($annotation_variable == $this->plugin_type_data['plugin_label_property']) {
        $annotation_data[$annotation_variable] = ClassAnnotation::Translation($this->component_data->plugin_label->value);
        continue;
      }

      // Hacky workaround for https://github.com/drupal-code-builder/drupal-code-builder/issues/97.
      if (isset($annotation_variable_info['type']) && $annotation_variable_info['type'] == '\Drupal\Core\Annotation\Translation') {
        // The annotation property value is translated.
        $annotation_data[$annotation_variable] = ClassAnnotation::Translation("TODO: replace this with a value");
        continue;
      }

      // It's an array. We don't know what the contents might be, but we can
      // provide a blank array as a template.
      if (isset($annotation_variable_info['type']) && $annotation_variable_info['type'] == 'array') {
        $annotation_data[$annotation_variable] = ['TODO' => 'array values'];
        continue;
      }

      // It's a plain string.
      $annotation_data[$annotation_variable] = "TODO: replace this with a value";
    }

    if (!empty($this->component_data->deriver->value)) {
      $annotation_data['deriver'] = '\Drupal\%module\Plugin\Derivative\\' . $this->component_data->deriver_plain_class_name->value;
    }

    $annotation = ClassAnnotation::{$annotation_class}($annotation_data);

    return $annotation;
  }

}
