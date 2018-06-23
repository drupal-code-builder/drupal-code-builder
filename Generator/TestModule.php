<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * Generator for a module that is used for testing.
 */
class TestModule extends Module {

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $component_data_definition['test_class_name'] = [
      'acquired' => TRUE,
    ];

    // Put the parent definitions after ours.
    $component_data_definition += parent::componentDataDefinition();

    // Remove properties for components that test modules don't need.
    foreach ([
      'module_help_text',
      'api',
      'readme',
      'phpunit_tests',
      'tests',
    ] as $property) {
      unset($component_data_definition[$property]);
    }

    // Note that a default is provided for the root_name property in the
    // requesting generator PHPUnitTest.

    // The package is always 'Testing' for test modules, so set this to
    // computed.
    $component_data_definition['module_package']['default'] = 'Testing';
    $component_data_definition['module_package']['computed'] = TRUE;

    // Don't need this, but info file generators expect it.
    $component_data_definition['module_dependencies']['internal'] = TRUE;

    $component_data_definition['component_base_path']['default'] = function($component_data) {
      return 'tests/modules/%module';
    };

    return $component_data_definition;
  }

}
