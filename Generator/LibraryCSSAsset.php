<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a CSS library asset.
 *
 * @see \DrupalCodeBuilder\Generator\Library
 */
class LibraryCSSAsset extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'filename' => [
        'label' => 'Filename',
        'description' => "The filename, without the extension or the subfolder.",
        'required' => TRUE,
      ],
      'style_type' => [
        'label' => 'Style type',
        'options' => [
          'base' => 'Base: CSS reset/normalize plus HTML element styling',
          'layout' => 'Layout: macro arrangement of a web page, including any grid systems',
          'component' => 'Component: discrete, reusable UI elements',
          'state' => 'State: styles that deal with client-side changes to components',
          'theme' => 'Theme: purely visual styling (“look-and-feel”) for a component',
        ],
        'required' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
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
  protected function buildComponentContents($children_contents) {
    $style_type = $this->component_data['style_type'];

    $yaml_data['css'][$style_type] = [
      'css/' . $this->component_data['filename'] . '.css' => [],
    ];

    return [
      'asset' => [
        'role' => 'asset',
        'content' => $yaml_data,
      ],
    ];
  }

}
