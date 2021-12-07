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
        ->setRequired(TRUE),
      'readable_name' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components['asset_file'] = [
      'component_type' => 'JavaScriptFile',
      // TODO: do this in property processing!
      'filename' => 'js/' . $this->component_data['filename'] . '.js',
    ];

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
    $yaml_data['js'] = [
      'js/' . $this->component_data['filename'] . '.js' => [],
    ];

    return [
      'asset' => [
        'role' => 'asset',
        'content' => $yaml_data,
      ],
    ];
  }
}
