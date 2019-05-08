<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a module library.
 */
class Library extends BaseGenerator {

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $component_data_definition = parent::componentDataDefinition() + [
      'library_name' => [
        'label' => 'Library machine-readable name',
        'default' => 'my_library',
        'required' => TRUE,
      ],
      'version' => [
        'label' => 'The version number',
        'default' => '1.x',
        'required' => TRUE,
      ],
      'css_assets' => [
        'label' => 'CSS file',
        'format' => 'compound',
        'component_type' => 'LibraryCSSAsset',
      ],
      'js_assets' => [
        'label' => 'JS file',
        'format' => 'compound',
        'component_type' => 'LibraryJSAsset',
      ],
      'dependencies' => [
        'label' => 'Library dependencies',
        'format' => 'array',
        'default' => [],
        'required' => FALSE,
        'process_default' => TRUE,
      ],
      'header' => [
        'label' => 'Header',
        'description' => "Whether to attach this library's JS files to the HEAD section of the page, rather than the bottom.",
        'format' => 'boolean',
      ],
      'readable_name' => static::PROPERTY_ACQUIRED,
    ];

    $component_data_definition['filename']['default'] = '%module.libraries.yml';
    $component_data_definition['filename']['required'] = TRUE;

    return $component_data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = [
      "%module.libraries.yml" => [
        'component_type' => 'YMLFile',
        'filename' => "%module.libraries.yml",
      ],
    ];

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return "%self:%module.libraries.yml";
  }


  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $assets_yaml_data = [];
    foreach ($this->filterComponentContentsForRole($children_contents, 'asset') as $component_name => $component_yaml_data) {
      $assets_yaml_data = array_merge_recursive($assets_yaml_data, $component_yaml_data);
    }

    $library_data = [
      'version' => $this->component_data['version'],
    ]
    + $assets_yaml_data;

    if (!empty($this->component_data['dependencies'])) {
      $library_data['dependencies'] = $this->component_data['dependencies'];
    }
    if (!empty($this->component_data['header'])) {
      $library_data['header'] = TRUE;
    }

    $yaml_data = [
      $this->component_data['library_name'] => $library_data
    ];

    return [
      'library' => [
        'role' => 'yaml',
        'content' => $yaml_data,
      ],
    ];
  }

}
