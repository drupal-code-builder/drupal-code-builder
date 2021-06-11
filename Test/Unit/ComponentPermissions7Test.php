<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the Permissions generator class.
 */
class ComponentPermissions7Test extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected $drupalMajorVersion = 7;

  /**
   * Test Permissions component.
   */
  function testPermissionsGenerationTests() {
    $permission_name = 'my permission name';

    // Create a module.
    $module_name = 'testmodule';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'permissions' => [
        0 => [
          'permission' => $permission_name,
        ],
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "Expected number of files is returned.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasHookImplementation('hook_permission', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $this->assertFunctionCode($module_file, "{$module_name}_permission", "permissions['$permission_name']");
  }

}
