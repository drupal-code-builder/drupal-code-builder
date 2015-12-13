<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Service.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for a service on Drupal 8.
 *
 * TODO: figure out how to say this is for D8 only.
 */
class Service extends PHPClassFile {

  /**
   * Set properties relating to class name.
   */
  protected function setClassNames($component_name) {
    // The service name is its ID as a service.
    // This will start with the module name, eg 'foo.bar'.
    // implode and ucfirst()
    $service_id = $component_name;
    $service_id_pieces = explode('.', $service_id);
    $class_name_pieces = array_map('ucfirst', $service_id_pieces);
    // Prefix the qualified class name with 'Drupal' and the module name.
    array_unshift($class_name_pieces, '%Module');
    array_unshift($class_name_pieces, 'Drupal');
    $qualified_class_name = implode('\\', $class_name_pieces);

    parent::setClassNames($qualified_class_name);
  }

  /**
   * Return an array of subcomponent types.
   */
  protected function requiredComponents() {
    $yaml_data = [];

    $yaml_data['services'] = [
      "%module.$this->name" => [
        'class' => $this->qualified_class_name,
        'arguments' => [],
      ],
    ];

    $components = array(
      '%module.services.yml' => array(
        'component_type' => 'YMLFile',
        'yaml_data' => $yaml_data,
      ),
    );

    return $components;
  }

  /**
   * Build the code files.
   */
  function collectFiles(&$files) {
    // Our component name is our future filename, with the token '%module' to
    // be replaced.
    $this->filename = str_replace('%module', $this->base_component->component_data['root_name'], $this->name);

    $files[$this->name] = array(
      'path' => 'src',
      'filename' => $this->filename,
      'body' => $this->file_contents(),
      // We join code files up on a single newline. This means that each
      // component is responsible for ending its own lines.
      'join_string' => "\n",
    );
  }

}
