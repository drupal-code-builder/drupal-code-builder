<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Tests7.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Component generator: tests.
 */
class Tests7 extends Tests {

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = array();

    $components['info_class'] = array(
      'component_type' => 'InfoProperty',
      'property_name' => 'files[]',
      'property_value' => 'tests/%module.test',
    );
    return $components;
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $files = parent::getFileInfo();

    $module_root_name = $this->root_component->component_data['root_name'];

    // Change the file location for D7.
    $files['%module.test']['path'] = 'tests';
    $files['%module.test']['filename'] = "%module.test";

    return $files;
  }

}