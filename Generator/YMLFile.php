<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\YMLFile.
 */

namespace ModuleBuider\Generator;

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

    $yaml_parser = new \Symfony\Component\Yaml\Yaml;
    $yaml = $yaml_parser->dump($this->component_data['yaml_data'], 2, 2);
    //drush_print_r($yaml);

    // Because the yaml is all built for us, this is just a singleton array.
    $body = array($yaml);

    $files[$this->filename] = array(
      'path' => '', // Means base folder.
      'filename' => $this->filename,
      'body' => $body,
      // We join code files up on a single newline. This means that each
      // component is responsible for ending its own lines.
      'join_string' => "\n",
    );
  }

}
