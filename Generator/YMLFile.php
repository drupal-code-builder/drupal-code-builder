<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for general YML files.
 *
 * Expects an array of data to output as YAML in the 'yaml_data' property.
 *
 * Note that replacement tokens should be avoided in YAML properties, as the
 * initial '%' causes the property to be quoted by the Symfony YAML dumper,
 * apparently  unnecessarily once the token is replaced.
 */
class YMLFile extends File {

  /**
   * The value of the indent parameter to pass to the YAML dumper.
   *
   * @var int
   */
  const YAML_INDENT = 2;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'yaml_data' => [
        'label' => 'The data for the YAML file.',
        'format' => 'array',
        'internal' => TRUE,
      ],
      'yaml_inline_level' => [
        'label' => 'The level at which to switch YAML properties to inline formatting.',
        'format' => 'string',
        'default' => 6,
        'internal' => TRUE,
      ],
      'line_break_between_blocks' => [
        'label' => 'Whether to add line breaks between the top-level properties.',
        'format' => 'boolean',
        'default' => FALSE,
        'internal' => TRUE,
      ],
    ];
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

    return array(
      'path' => '', // Means base folder.
      'filename' => $this->filename,
      'body' => $this->getYamlBody($this->component_data['yaml_data']),
      // We join code files up on a single newline. This means that each
      // component is responsible for ending its own lines.
      'join_string' => "\n",
    );
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

    $yaml_parser_inline_switch_level = $this->component_data['yaml_inline_level'];

    if ($this->component_data['line_break_between_blocks']) {
      $body = [];

      foreach (range(0, count($yaml_data_array) -1 ) as $index) {
        $yaml_slice = array_slice($yaml_data_array, $index, 1);

        // Each YAML piece comes with a terminal newline, so when these are
        // joined there will be the desired blank line between each section.
        $body[] = $yaml_parser->dump($yaml_slice, $yaml_parser_inline_switch_level, static::YAML_INDENT);
      }
    }
    else {
      $yaml = $yaml_parser->dump($yaml_data_array, $yaml_parser_inline_switch_level, static::YAML_INDENT);

      // Because the yaml is all built for us, this is just a singleton array.
      $body = array($yaml);
    }
    //drush_print_r($yaml);

    return $body;
  }

}
