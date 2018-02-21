<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests the AdminSettingsForm generator class.
 */
class ComponentAdminSettings8Test extends TestBaseComponentGeneration {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

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
    $this->assertWellFormedPHP($form_file);
    $this->assertDrupalCodingStandards($form_file);
    $this->assertNoTrailingWhitespace($form_file, "The form class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($form_file);

    $this->assertNamespace(['Drupal', $module_name, 'Form'], $form_file, "The form class file contains contains the expected namespace.");
    $this->assertClass('AdminSettingsForm', $form_file, "The form class file contains contains the expected class.");
    // TODO: check the methods.

    // Check the .routing file.
    $routing_file = $files["$module_name.routing.yml"];
    $yaml_tester = new YamlTester($routing_file);

    $expected_route_name = 'testmodule.admin.config.TODO-SECTION.testmodule';
    $yaml_tester->assertHasProperty($expected_route_name, "The routing file has the property for the admin route.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'path'], '/admin/config/TODO-SECTION/testmodule', "The routing file declares the route path.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_form'], '\Drupal\testmodule\Form\AdminSettingsForm', "The routing file declares the route form.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_title'], 'Administer Test module', "The routing file declares the route title.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'requirements', '_permission'], 'TODO: set permission machine name', "The routing file declares the route permission.");

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer testmodule', "The permissions file declares the admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer testmodule', 'title'], 'administer testmodule', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer testmodule', 'description'], 'Administer testmodule', "The permission has the expected title.");

    // Check the .info file.
    $info_file = $files["$module_name.info.yml"];
    $yaml_tester = new YamlTester($info_file);

    $yaml_tester->assertPropertyHasValue('name', $module_data['readable_name'], "The info file declares the module name.");
    $yaml_tester->assertPropertyHasValue('description', $module_data['short_description'], "The info file declares the module description.");
    $yaml_tester->assertPropertyHasValue('core', "8.x", "The info file declares the core version.");
    $yaml_tester->assertPropertyHasValue('configure', "admin/config/TODO-SECTION/$module_name", "The info file declares the configuration path.");
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
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer testmodule', "The permissions file declares the admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer testmodule', 'title'], 'administer testmodule', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer testmodule', 'description'], 'Administer testmodule', "The permission has the expected title.");

    $yaml_tester->assertHasProperty('access testmodule', "The permissions file declares the requested permission.");
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
     $yaml_tester = new YamlTester($routing_file);

     $expected_route_name = 'testmodule.admin.config.TODO-SECTION.testmodule';
     $yaml_tester->assertHasProperty($expected_route_name, "The routing file has the property for the admin route.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'path'], '/admin/config/TODO-SECTION/testmodule', "The routing file declares the route path.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_form'], '\Drupal\testmodule\Form\AdminSettingsForm', "The routing file declares the route form.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_title'], 'Administer Test module', "The routing file declares the route title.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'requirements', '_permission'], 'TODO: set permission machine name', "The routing file declares the route permission.");

     $yaml_tester->assertPropertyHasValue(['testmodule.requested.route.path', 'path'], "/requested/route/path", "The routing file declares the requested path.");
  }

}
