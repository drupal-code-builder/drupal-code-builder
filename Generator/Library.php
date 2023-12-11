<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for a module library.
 */
class Library extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'library_name' => PropertyDefinition::create('string')
        ->setLabel("Library machine-readable name")
        ->setLiteralDefault("my_library")
        ->setRequired(TRUE),
      'version' => PropertyDefinition::create('string')
        ->setLabel("The version number")
        ->setLiteralDefault("1.x")
        ->setRequired(TRUE),
      'css_assets' => static::getLazyDataDefinitionForGeneratorType('LibraryCSSAsset')
        ->setLabel("CSS file")
        ->setMultiple(TRUE),
      'js_assets' => static::getLazyDataDefinitionForGeneratorType('LibraryJSAsset')
        ->setLabel("JS file")
        ->setMultiple(TRUE),
      'dependencies' => PropertyDefinition::create('string')
        ->setLabel("Library dependencies")
        ->setDescription("The sample code in the generated JS file requires core/jquery and core/drupal.")
        ->setMultiple(TRUE)
        ->setLiteralDefault([
          // The sample code in JavaScriptFile assumes these dependencies.
          'core/jquery',
          'core/drupal',
        ]),
      'header' => PropertyDefinition::create('boolean')
        ->setLabel("Header")
        ->setDescription("Whether to attach this library's JS files to the HEAD section of the page, rather than the bottom."),
      'readable_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            // TODO: fix this! Should NOT have to reach this high, WTF!
            ->setExpression("machineToLabel(get('..:..:..:root_name'))")
        ),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
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
  public function getContents(): array {
    $assets_yaml_data = [];
    foreach ($this->containedComponents['element'] as $child_item) {
      $assets_yaml_data = array_merge_recursive($assets_yaml_data, $child_item->getContents());
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

    return $yaml_data;
  }

}
