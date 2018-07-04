<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator base class for module info file.
 */
class Info extends File {

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

}
