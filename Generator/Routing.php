<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\Routing.
 */

namespace ModuleBuider\Generator;

/**
 * Generator for the routing.yml file.
 *
 * Note this only is requested for Drupal 8.
 *
 * DEVELOPER NOTES: there is no way to request this via Drush yet.
 * To see this in action, hack Module::requiredComponents() to return something like:
 $dev_components = array(
   'path/foo' => array(
     'component_type' => 'RouterItem',
     'title' => 'Foo',
   ),
   'path/bar' => array(
     'component_type' => 'RouterItem',
     'title' => 'Bar',
   ),
 );
 *
 * @see RouterItem8
 */
class Routing extends File {

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
    $files['routing'] = array(
      'path' => '', // Means base folder.
      'filename' => $this->base_component->component_data['module_root_name'] . '.routing.yml',
      'body' => array(
        $this->code_body(),
      ),
      'join_string' => "\n",
    );
  }

  /**
   * Return the main body of the file code.
   *
   * @return
   *  An array of chunks of text for the code body.
   */
  function code_body() {
    // TODO!!!

    /*
    $yaml_parser = new \Symfony\Component\Yaml\Yaml;
    $yaml = $yaml_parser->dump($lines, 2, 2);
    */
    return 'TODO! this is the routing file!';
  }

}
