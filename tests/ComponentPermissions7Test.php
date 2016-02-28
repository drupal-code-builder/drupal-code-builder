<?php

/**
 * @file
 * Contains ComponentPermissions7Test.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/DrupalCodeBuilderTestBase.php';

/**
 * Tests the Permissions generator class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/ComponentPermissions7Test.php
 * @endcode
 */
class ComponentPermissions7Test extends DrupalCodeBuilderTestBase {

  /**
   * Test Permissions component.
   */
  function testPermissionsGenerationTests() {
    $this->setupDrupalCodeBuilder(7);

    $permission_name = 'my permission name';

    // Create a module.
    $module_name = 'testmodule';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'permissions' => array($permission_name),
      'requested_components' => array(
      ),
      'requested_build' => array(
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(1, $files, "One file returned.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $this->assertNoTrailingWhitespace($module_file, "The module file contains no trailing whitespace.");
    $this->assertHookImplementation($module_file, 'hook_permission', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $this->assertFunctionCode($module_file, "{$module_name}_permission", "permissions['$permission_name']");
  }

}
