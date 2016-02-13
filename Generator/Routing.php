<?php

/**
 * @file
 * Definition of ModuleBuilder\Generator\Routing.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for the routing.yml file.
 *
 * Note this only is requested for Drupal 8.
 *
 * @see RouterItem8
 */
class Routing extends YMLFile {

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   An array of data for the component. Valid properties are:
   *    - 'routing_items': A numeric array of router items. Each item is an
   *      array with the properties:
   *      - 'path': The path for the item, without the initial '/'.
   */
  function __construct($component_name, $component_data = array()) {
    parent::__construct($component_name, $component_data);
  }

  /**
   * Build the code files.
   */
  function collectFiles(&$files) {
    foreach ($this->component_data['routing_items'] as $routing_item) {
      $this->component_data['yaml_data'][$routing_item['path']] = array(
        'path' => $routing_item['path'],
        'defaults' => array(
          '_title' => $routing_item['title'],
        ),
        'requirements' => array(
          '_permission' => 'TODO: set permission machine name',
        ),
      );
    }

    parent::collectFiles($files);
  }

}
