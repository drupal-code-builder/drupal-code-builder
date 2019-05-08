<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * JavaScript asset file generator.
 *
 * Most of the work is done in Library[CSS/JS]Asset.
 */
class JavaScriptFile extends AssetFile {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'readable_name' => static::PROPERTY_ACQUIRED,
    ];
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $camel_name = CaseString::snake($this->component_data['root_component_name'])->camel();

    $body = <<<EOT
/**
 * @file
 * Defines Javascript behaviors for the {$this->component_data['readable_name']} module.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.{$camel_name} = {
    attach: function (context, settings) {
    }
  };
})(jQuery, Drupal, drupalSettings);

EOT;

    return [
      'path' => '', // Means base folder.
      'filename' => $this->component_data['filename'],
      'body' => [$body],
    ];
  }

}
