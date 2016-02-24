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
   * Test Admin Settings component.
   */
  function testAdminSettingsGenerationTests() {
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
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertEquals(count($files), 3, "Three files are returned.");

    // Check the admin.inc file code.
    $admin_file = $files["$module_name.admin.inc"];
    $this->assertNoTrailingWhitespace($admin_file, "The admin.inc file contains no trailing whitespace.");
    $this->assertFunction($admin_file, "{$module_name}_settings_form", "The admin.inc file contains the settings form builder.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $this->assertNoTrailingWhitespace($module_file, "The module file contains no trailing whitespace.");
    $this->assertHookImplementation($module_file, 'hook_permission', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $this->assertFunctionCode($module_file, "{$module_name}_permission", "permissions['administer $module_name']");
    $this->assertHookImplementation($module_file, 'hook_menu', $module_name, "The module file contains a function declaration that implements hook_permission().");

    // Check the .info file.
    $info_file = $files["$module_name.info"];

    $this->assertInfoLine($info_file, 'name', $module_data['readable_name'], "The info file declares the module name.");
    $this->assertInfoLine($info_file, 'description', $module_data['short_description'], "The info file declares the module description.");
    $this->assertInfoLine($info_file, 'core', "7.x", "The info file declares the core version.");
    $this->assertInfoLine($info_file, 'configure', "admin/config/TODO-SECTION/$module_name", "The info file declares the configuration path.");
  }

  /**
   * Test Admin Settings component with other hooks.
   */
  function testAdminSettingsOtherHooksTest() {
    $this->setupModuleBuilder(7);

    // Create a module.
    $module_name = 'testmodule';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
        'init'
      ),
      'requested_components' => array(
        'AdminSettingsForm' => 'AdminSettingsForm',
        'info' => 'info',
      ),
      'requested_build' => array(
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $this->assertHookImplementation($module_file, 'hook_permission', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $this->assertHookImplementation($module_file, 'hook_menu', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $this->assertHookImplementation($module_file, 'hook_init', $module_name, "The module file contains a function declaration that implements hook_permission().");
  }

  /**
   * Test Admin Settings component with other permissions.
   */
  function testAdminSettingsOtherPermsTest() {
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
      'permissions' => array(
        'access testmodule',
      ),
      'requested_components' => array(
        'AdminSettingsForm' => 'AdminSettingsForm',
        'info' => 'info',
      ),
      'requested_build' => array(
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $this->assertHookImplementation($module_file, 'hook_permission', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $this->assertFunctionCode($module_file, "{$module_name}_permission", "permissions['administer $module_name']");
    $this->assertFunctionCode($module_file, "{$module_name}_permission", "permissions['access testmodule']");
  }

}