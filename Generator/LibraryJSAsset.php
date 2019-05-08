<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a module library asset
 */
class LibraryJSAsset extends BaseGenerator {

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
      'readable_name' => static::PROPERTY_ACQUIRED,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
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
