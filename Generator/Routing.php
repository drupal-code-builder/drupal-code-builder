<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Routing.
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
   *   An array of data for the component.
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
  }

}
