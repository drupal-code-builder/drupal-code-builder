<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for a JS library asset.
 */
class LibraryJSAsset extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'filename' => PropertyDefinition::create('string')
        ->setLabel('Filename')
        ->setDescription("The filename, without the extension or the subfolder.")
        ->setRequired(TRUE)
        ->setProcessing(function ($data) {
          if (!filter_var($data->value, FILTER_VALIDATE_URL)) {
            // DIRTY HACK! processing gets repeated ARGH. TODO ARRRRRGH.
            if (substr($data->value, 0, 3) != 'js/') {
              $data->value = 'js/' . $data->value . '.js';
            }
          }
        }),
      'readable_name' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = [];

    if (!filter_var($this->component_data['filename'], FILTER_VALIDATE_URL)) {
      $components['asset_file'] = [
        'component_type' => 'JavaScriptFile',
        'filename' => $this->component_data['filename'],
      ];
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%requester';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    if (filter_var($this->component_data['filename'], FILTER_VALIDATE_URL)) {
      $value = [
        'type' => 'external',
        'minified' => TRUE,
      ];
    }
    else {
      $value = [];
    }

    $yaml_data['js'] = [
      $this->component_data['filename'] => $value,
    ];

    return [
      'asset' => [
        'role' => 'asset',
        'content' => $yaml_data,
      ],
    ];
  }
}
