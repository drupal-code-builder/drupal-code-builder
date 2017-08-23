<?php

/**
 * @file
 * Contains APIComponentDataTest.
 */

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the AdminSettingsForm generator class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/APIComponentDataTest.php
 * @endcode
 */
class APIComponentDataTest extends TestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(7);
  }

  /**
   * Test defaults in component data.
   */
  function testComponentDataDefaults() {
    $mb_task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    $component_data_info = $mb_task_handler_generate->getRootComponentDataInfo();

    // Check that the generate system's processing populated defaults values for
    // 'format' and 'required'.
    $this->assertEquals(FALSE, $component_data_info['module_package']['required'], "The 'required' info property has a default value filled in.");
    $this->assertEquals('string', $component_data_info['module_package']['format'], "The 'format' info property has a default value filled in.");

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
