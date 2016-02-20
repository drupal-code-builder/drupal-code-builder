<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Tests7.
 */

namespace ModuleBuilder\Generator;

/**
 * Component generator: tests.
 */
class Tests7 extends Tests {

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $files = parent::getFileInfo();

    $module_root_name = $this->base_component->component_data['root_name'];

    // Change the file location for D7.
    $files['module.test']['path'] = 'tests';
    $files['module.test']['filename'] = "$module_root_name.test";

    return $files;
  }

}