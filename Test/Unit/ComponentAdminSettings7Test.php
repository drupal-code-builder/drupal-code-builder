<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the AdminSettingsForm generator class.
 */
class ComponentAdminSettings7Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 7;

  /**
   * Test Admin Settings component.
   */
  function testAdminSettingsGenerationTests() {
    // Create a module.
    $module_name = 'testmodule';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'settings_form' => TRUE,
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'testmodule.info',
      'testmodule.module',
      'testmodule.admin.inc',
    ], $files);

    // Check the admin.inc file code.
    $admin_file = $files["$module_name.admin.inc"];
    $php_tester = new PHPTester($admin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasFunction("{$module_name}_settings_form", $admin_file, "The admin.inc file contains the settings form builder.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $php_tester = new PHPTester($module_file);
    $php_tester->assertHasHookImplementation('hook_permission', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $this->assertFunctionCode($module_file, "{$module_name}_permission", "permissions['administer $module_name']");
    $php_tester->assertHasHookImplementation('hook_menu', $module_name, "The module file contains a function declaration that implements hook_menu().");

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
      'settings_form' => TRUE,
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $php_tester = new PHPTester($module_file);
    $php_tester->assertHasHookImplementation('hook_permission', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $php_tester->assertHasHookImplementation('hook_menu', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $php_tester->assertHasHookImplementation('hook_init', $module_name, "The module file contains a function declaration that implements hook_permission().");
  }

  /**
   * Test Admin Settings component with other permissions.
   */
  function testAdminSettingsOtherPermsTest() {
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
          'permission' => 'access testmodule',
        ),
      ),
      'settings_form' => TRUE,
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $php_tester = new PHPTester($module_file);
    $php_tester->assertHasHookImplementation('hook_permission', $module_name, "The module file contains a function declaration that implements hook_permission().");
    $this->assertFunctionCode($module_file, "{$module_name}_permission", "permissions['administer $module_name']");
    $this->assertFunctionCode($module_file, "{$module_name}_permission", "permissions['access testmodule']");
  }

}
