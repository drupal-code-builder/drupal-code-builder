<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the Permissions generator class.
 */
class ComponentPermissions7Test extends TestBaseComponentGeneration {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(7);
  }

  /**
   * Test Permissions component.
   */
  function testPermissionsGenerationTests() {
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
      'permissions' => array(
        1 => array(
          'permission' => $permission_name,
        ),
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "Expected number of files is returned.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $this->assertWellFormedPHP($module_file);
    $this->assertNoTrailingWhitespace($module_file, "The module file contains no trailing whitespace.");
    $this->assertHookImplementation($module_file, 'hook_permission', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $this->assertFunctionCode($module_file, "{$module_name}_permission", "permissions['$permission_name']");
  }

}
