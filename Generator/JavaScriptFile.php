<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\File\CodeFile;
use DrupalCodeBuilder\Definition\PropertyListInterface;
use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * JavaScript asset file generator.
 *
 * Most of the work is done in Library[CSS/JS]Asset.
 */
class JavaScriptFile extends AssetFile {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'readable_name' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFileInfo(): CodeFile {
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

    return new CodeFile([$body]);
  }

}
