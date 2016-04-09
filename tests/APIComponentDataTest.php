<?php

/**
 * @file
 * Contains APIComponentDataTest.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/DrupalCodeBuilderTestBase.php';

/**
 * Tests the AdminSettingsForm generator class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/APIComponentDataTest.php
 * @endcode
 */
class APIComponentDataTest extends DrupalCodeBuilderTestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(7);
  }

  /**
   * Test defaults in component data.
   */
  function testComponentDataDefaults() {
    $mb_task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    $component_data_info = $mb_task_handler_generate->getRootComponentDataInfo();

    $module_name = 'my_test_module';

    $component_data = array(
      'base' => 'module',
      'root_name' => $module_name,
    );

    // Test the readable name default value is provided, based on the module
    // machine name.
    $property_name = 'readable_name';
    $mb_task_handler_generate->prepareComponentDataProperty($property_name, $component_data_info[$property_name], $component_data);

    $this->assertEquals('My Test Module', $component_data['readable_name']);
  }

}
