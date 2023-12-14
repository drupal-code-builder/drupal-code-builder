<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for a CSS library asset.
 *
 * @see \DrupalCodeBuilder\Generator\Library
 */
class LibraryCSSAsset extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'filename' => PropertyDefinition::create('string')
        ->setLabel('Filename')
        ->setDescription("The filename, without the extension or the subfolder.")
        ->setRequired(TRUE),
      'style_type' => PropertyDefinition::create('string')
        ->setLabel('Style type')
        ->setOptionsArray([
          'base' => 'Base: CSS reset/normalize plus HTML element styling',
          'layout' => 'Layout: macro arrangement of a web page, including any grid systems',
          'component' => 'Component: discrete, reusable UI elements',
          'state' => 'State: styles that deal with client-side changes to components',
          'theme' => 'Theme: purely visual styling (“look-and-feel”) for a component',
        ])
        ->setRequired(TRUE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components['asset_file'] = [
      'component_type' => 'AssetFile',
      // TODO: do this in property processing!
      'filename' => 'css/' . $this->component_data['filename'] . '.css',
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
  public function getContents(): array {
    $style_type = $this->component_data['style_type'];

    $yaml_data['css'][$style_type] = [
      'css/' . $this->component_data['filename'] . '.css' => [],
    ];

    return $yaml_data;
  }

}
