<?php

/**
 * @file
 * Definition of ModuleBuilder\Generator\YMLFile.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for general YML files.
 *
 * Expects an array of data to output as YAML in the 'yaml_data' property.
 */
class YMLFile extends File {

  /**
   * Build the code files.
   */
  function collectFiles(&$files) {
    // Our component name is our future filename, with the token '%module' to
    // be replaced.
    $this->filename = str_replace('%module', $this->base_component->component_data['root_name'], $this->name);

    $files[$this->filename] = array(
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
    $yaml = $yaml_parser->dump($yaml_data_array, 6, 2);
    //drush_print_r($yaml);

    // Replace variables
    $variables = $this->getReplacements();
    $yaml = strtr($yaml, $variables);

    // Because the yaml is all built for us, this is just a singleton array.
    $body = array($yaml);

    return $body;
  }

}
