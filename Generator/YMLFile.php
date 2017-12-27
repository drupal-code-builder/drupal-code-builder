<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for general YML files.
 *
 * Expects an array of data to output as YAML in the 'yaml_data' property.
 */
class YMLFile extends File {

  /**
   * {@inheritdoc}
   */
  public function mergeComponentData($additional_component_data) {
    parent::mergeComponentData($additional_component_data);

    // The hacky yaml_inline_level property may not be an array!
    if (isset($this->component_data['yaml_inline_level']) && is_array($this->component_data['yaml_inline_level'])) {
      $this->component_data['yaml_inline_level'] = reset($this->component_data['yaml_inline_level']);
    }
  }

  /**
   * {@inheritdoc}
   */
  function buildComponentContents($children_contents) {
    $yaml_data = array();
    foreach ($this->filterComponentContentsForRole($children_contents, 'yaml') as $component_name => $component_yaml_data) {
      $yaml_data += $component_yaml_data;
    }

    // TEMPORARY, until Generate task handles returned contents.
    if (!empty($yaml_data)) {
      // Only zap this if children provide something, as other components still
      // set this property by request.
      $this->component_data['yaml_data'] = $yaml_data;
    }

    return array();
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    // Our component name is our future filename. Tokens such as '%module' are
    // replaced by assembleFiles().
    $this->filename = $this->name;

    $files[$this->getUniqueID()] = array(
      'path' => '', // Means base folder.
      'filename' => $this->filename,
      'body' => $this->getYamlBody($this->component_data['yaml_data']),
      // We join code files up on a single newline. This means that each
      // component is responsible for ending its own lines.
      'join_string' => "\n",
    );
    return $files;
  }

  /**
   * Get the YAML body for the file.
   *
   * @param $yaml_data_array
   *  An array of data to convert to YAML.
   *
   * @return
   *  An array containing the YAML string.
   */
  protected function getYamlBody($yaml_data_array) {
    $yaml_parser = new \Symfony\Component\Yaml\Yaml;

    // TODO: document and declare this property.
    $yaml_parser_inline_switch_level = $this->component_data['yaml_inline_level']
      ?? 6;

    $yaml = $yaml_parser->dump($yaml_data_array, $yaml_parser_inline_switch_level, 2);
    //drush_print_r($yaml);

    // Because the yaml is all built for us, this is just a singleton array.
    $body = array($yaml);

    return $body;
  }

}
