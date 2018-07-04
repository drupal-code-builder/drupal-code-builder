<?php

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

    // Declare the class file in the module's .info file.
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
    $file = parent::getFileInfo();

    // Change the file location for D7.
    $file['path'] = 'tests';
    $file['filename'] = "%module.test";

    return $file;
  }

}
