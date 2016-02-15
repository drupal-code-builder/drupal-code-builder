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
   * Constructor method; sets the component data.
   *
   * The component name is taken to be the service ID.
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
  }

  /**
   * Set properties relating to class name.
   */
  protected function setClassNames($component_name) {
    // The service name is its ID as a service.
    // This will start with the module name, eg 'foo.bar'.
    // implode and ucfirst()
    $service_id = $component_name;
    $service_id_pieces = explode('.', $service_id);
    // Create an unqualified class name by turning this into camel case.
    $unqualified_class_name = implode('', array_map('ucfirst', $service_id_pieces));
    // Form the full class name by adding a namespace Drupal\MODULE.
    $class_name_pieces = array(
      'Drupal',
      $this->base_component->component_data['root_name'],
      $unqualified_class_name,
    );
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

}
