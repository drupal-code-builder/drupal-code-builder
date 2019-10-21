<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator base class for module info file.
 */
class Info extends File {

  /**
   * The order of keys in the info file.
   *
   * @todo: Make this protected once our minimum PHP version is 7.1.
   */
  const INFO_LINE_ORDER = [
    'name',
    'type',
    'description',
    'package',
    'version',
    'core',
    'dependencies',
  ];

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    // Properties acquired from the requesting root component.
    $plugin_type_properties = [
      'readable_name',
      'short_description',
      'module_dependencies',
      'module_package',
    ];
    foreach ($plugin_type_properties as $property_name) {
      $data_definition[$property_name] = [
        'acquired' => TRUE,
      ];
    }

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $lines = array();
    foreach ($this->filterComponentContentsForRole($children_contents, 'infoline') as $component_name => $component_lines) {
      // Assume that children components don't tread on each others' toes and
      // provide the same property names.
      $lines += $component_lines;
    }

    // Temporary, until Generate handles the return from this.
    $this->extraLines = $lines;
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    return array(
      'path' => '',
      'filename' => '%module.info',
      'body' => $this->file_body(),
      'build_list_tags' => ['info'],
    );
  }

  /**
   * Gets an array of info file lines in the correct order to be populated.
   *
   * @return array
   *   The array of lines.
   */
  protected function getInfoFileEmptyLines() {
    return array_fill_keys(self::INFO_LINE_ORDER, []);
  }

}
