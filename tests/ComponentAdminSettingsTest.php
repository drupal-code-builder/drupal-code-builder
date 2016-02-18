<?php

/**
 * @file
 * Contains ComponentAdminSettingsTest.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/ModuleBuilderTestBase.php';

/**
 * Tests the AdminSettingsForm generator class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/ComponentAdminSettingsTest.php
 * @endcode
 */
class ComponentAdminSettingsTest extends ModuleBuilderTestBase {

  /**
   * Test Tests component.
   */
  function testModuleGenerationTests() {
    $this->setupModuleBuilder(7);

    // Create a module.
    $module_name = 'testmodule';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'requested_components' => array(
        'AdminSettingsForm' => 'AdminSettingsForm',
        'info' => 'info',
      ),
      'requested_build' => array(
        'info' => TRUE,
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertEquals(count($files), 3, "Two files are returned.");

    // TODO: check module code.

    // Check the .info file.
    $info_file = $files["$module_name.info"];

    $this->assertInfoLine($info_file, 'name', $module_data['readable_name'], "The info file declares the module name.");
    $this->assertInfoLine($info_file, 'description', $module_data['short_description'], "The info file declares the module description.");
    $this->assertInfoLine($info_file, 'core', "7.x", "The info file declares the core version.");
    $this->assertInfoLine($info_file, 'configure', "admin/config/TODO-SECTION/$module_name", "The info file declares the configuration path.");
  }

}