<?php

/**
 * @file
 * Contains ComponentAdminSettings8Test.
 */

namespace DrupalCodeBuilder\Test;

/**
 * Tests the AdminSettingsForm generator class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/ComponentAdminSettings8Test.php
 * @endcode
 */
class ComponentAdminSettings8Test extends TestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test Admin Settings component.
   */
  function testAdminSettingsGenerationTest() {
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

    $this->assertCount(4, $files, "The expected number of files are returned.");

    // Check the form class code.
    $form_file = $files['src/Form/AdminSettingsForm.php'];
    $this->assertNoTrailingWhitespace($form_file, "The form class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($form_file);

    $this->assertNamespace(['Drupal', $module_name, 'Form'], $form_file, "The form class file contains contains the expected namespace.");
    $this->assertClass('AdminSettingsForm', $form_file, "The form class file contains contains the expected class.");
    // TODO: check the methods.

    // Check the .routing file.
    $routing_file = $files["$module_name.routing.yml"];
    $this->assertYamlProperty($routing_file, 'path', "/admin/config/TODO-SECTION/$module_name", "The routing file declares the admin settings path.");
    $this->assertYamlProperty($routing_file, '_form', '\\Drupal\\testmodule\\Form\\AdminSettingsForm', "The routing file declares the _form controller.");
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

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $this->assertYamlProperty($permissions_file, 'title', "administer $module_name", "The permissions file declares the admin permission.");
    $this->assertYamlProperty($permissions_file, 'title', "access testmodule", "The permissions file declares the requested permission.");
   }

   /**
    * Test Admin Settings component with other router items.
    */
   function testAdminSettingsOtherRouterItemsTest() {
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
       'router_items' => array(
          'requested/route/path',
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
