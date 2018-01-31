<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the Permissions generator class.
 */
class ComponentPermissions8Test extends TestBaseComponentGeneration {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

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
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("$module_name.permissions.yml", $files, "The files list has a .permissions.yml file.");

    // Check the .permissions.yml file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $this->assertYamlProperty($permissions_file, 'title', $permission_name, "The permissions file declares the requested permission.");
  }

}
