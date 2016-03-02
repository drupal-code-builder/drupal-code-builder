<?php

/**
 * @file
 * Contains ComponentAdminSettings8Test.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/DrupalCodeBuilderTestBase.php';

/**
 * Tests the AdminSettingsForm generator class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/ComponentAdminSettings8Test.php
 * @endcode
 */
class ComponentAdminSettings8Test extends DrupalCodeBuilderTestBase {

  /**
   * Test Admin Settings component.
   */
  function testAdminSettingsGenerationTest() {
    $this->setupDrupalCodeBuilder(8);

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
      'requested_components' => array(
        'info' => 'info',
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(4, $files, "Four files are returned.");

    // Check the form class code.
    $form_file = $files['src/Form/AdminSettingsForm.php'];
    $this->assertNoTrailingWhitespace($form_file, "The form class file contains no trailing whitespace.");
    $this->assertClass($form_file, 'AdminSettingsForm', "The form class file contains contains the expected class.");
    // TODO: check the methods.

    // Check the .routing file.
    $routing_file = $files["$module_name.routing.yml"];
    $this->assertYamlProperty($routing_file, 'path', "/admin/config/TODO-SECTION/$module_name", "The routing file declares the admin settings path.");
    $this->assertYamlProperty($routing_file, '_title', 'Administer Test module', "The routing file declares the admin settings page title.");
    // TODO: check access.

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $this->assertYamlProperty($permissions_file, 'title', "administer $module_name", "The permissions file declares the admin permission.");

    // Check the .info file.
    $info_file = $files["$module_name.info.yml"];
    $this->assertYamlProperty($info_file, 'name', $module_data['readable_name'], "The info file declares the module name.");
    $this->assertYamlProperty($info_file, 'description', $module_data['short_description'], "The info file declares the module description.");
    $this->assertYamlProperty($info_file, 'core', "8.x", "The info file declares the core version.");
    $this->assertYamlProperty($info_file, 'configure', "admin/config/TODO-SECTION/$module_name", "The info file declares the configuration path.");
  }

  /**
   * Test Admin Settings component with other permissions.
   */
  function testAdminSettingsOtherPermsTest() {
    $this->setupDrupalCodeBuilder(8);

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
      'settings_form' => TRUE,
      'requested_components' => array(
        'info' => 'info',
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $this->assertYamlProperty($permissions_file, 'title', "administer $module_name", "The permissions file declares the admin permission.");
    $this->assertYamlProperty($permissions_file, 'title', "access testmodule", "The permissions file declares the requested permission.");
   }

   /**
    * Test Admin Settings component with other router items.
    */
   function testAdminSettingsOtherRouterItemsTest() {
     $this->setupDrupalCodeBuilder(8);

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
       'settings_form' => TRUE,
       'router_items' => array(
          'requested/route/path',
       ),
       'requested_components' => array(
         'info' => 'info',
       ),
       'readme' => FALSE,
     );
     $files = $this->generateModuleFiles($module_data);

     // Check the .routing file.
     $routing_file = $files["$module_name.routing.yml"];
     $this->assertYamlProperty($routing_file, 'path', "/admin/config/TODO-SECTION/$module_name", "The routing file declares the admin settings path.");
     $this->assertYamlProperty($routing_file, '_title', 'Administer Test module', "The routing file declares the admin settings page title.");

     $this->assertYamlProperty($routing_file, 'path', "/requested/route/path", "The routing file declares the requested path.");
  }

}
