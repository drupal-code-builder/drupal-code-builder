<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for the routing.yml file.
 *
 * Note this only is requested for Drupal 8.
 *
 * @see RouterItem
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
  function __construct($component_name, $component_data, $root_generator) {
    parent::__construct($component_name, $component_data, $root_generator);
  }

}
