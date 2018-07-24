<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests the AdminSettingsForm generator class.
 */
class ComponentAdminSettings8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test Admin Settings component.
   */
  function test8AdminSettingsGenerationTest() {
    // Create a module.
    $module_name = 'testmodule';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'settings_form' => [
        0 => [
          'parent_route' => 'system.admin_config_system',
        ],
      ],
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'testmodule.info.yml',
      'src/Form/AdminSettingsForm.php',
      'testmodule.routing.yml',
      'testmodule.links.menu.yml',
      'testmodule.permissions.yml',
      'config/schema/testmodule.schema.yml',
    ], $files);

    // Check the form class code.
    $form_file = $files['src/Form/AdminSettingsForm.php'];

    $php_tester = new PHPTester($form_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\testmodule\Form\AdminSettingsForm');
    $php_tester->assertClassHasParent('Drupal\Core\Form\FormBase');

    $method_tester = $php_tester->getMethodTester('getFormId');
    $method_tester->assertMethodDocblockHasInheritdoc();
    $method_tester->assertReturnsString('testmodule_settings_form');

    $form_builder_tester = $php_tester->getMethodTester('buildForm')->getFormBuilderTester();
    $form_builder_tester->assertElementCount(1);

    $php_tester->assertHasMethod('submitForm');

    // Check the schema file.
    $config_schema_file = $files['config/schema/testmodule.schema.yml'];
    $yaml_tester = new YamlTester($config_schema_file);

    $yaml_tester->assertHasProperty('testmodule.settings', "The schema file has the property for the settings.");
    $yaml_tester->assertPropertyHasValue(['testmodule.settings', 'type'], 'config_object');
    $yaml_tester->assertPropertyHasValue(['testmodule.settings', 'label'], 'Test module settings');
    $yaml_tester->assertPropertyHasValue(['testmodule.settings', 'mapping'], []);

    // Check the .routing file.
    $routing_file = $files["$module_name.routing.yml"];
    $yaml_tester = new YamlTester($routing_file);

    $expected_route_name = 'testmodule.settings';
    $yaml_tester->assertHasProperty($expected_route_name, "The routing file has the property for the admin route.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'path'], '/admin/config/system/testmodule', "The routing file declares the route path.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_form'], '\Drupal\testmodule\Form\AdminSettingsForm', "The routing file declares the route form.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_title'], 'Administer Test module', "The routing file declares the route title.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'requirements', '_permission'], 'administer testmodule', "The routing file declares the route permission.");

    // Check the menu links file.
    $links_file = $files['testmodule.links.menu.yml'];
    $yaml_tester = new YamlTester($links_file);

    $yaml_tester->assertHasProperty('testmodule.settings', "The links file has the link name.");
    $yaml_tester->assertPropertyHasValue(['testmodule.settings', 'title'], 'Test module');
    $yaml_tester->assertPropertyHasValue(['testmodule.settings', 'description'], 'Configure the settings for Test module.');
    $yaml_tester->assertPropertyHasValue(['testmodule.settings', 'route_name'], 'testmodule.settings');
    $yaml_tester->assertPropertyHasValue(['testmodule.settings', 'parent'], 'system.admin_config_system');

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer testmodule', "The permissions file declares the admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer testmodule', 'title'], 'Administer testmodule', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer testmodule', 'description'], 'Administer testmodule', "The permission has the expected title.");

    // Check the .info file.
    $info_file = $files["$module_name.info.yml"];
    $yaml_tester = new YamlTester($info_file);

    $yaml_tester->assertPropertyHasValue('name', $module_data['readable_name'], "The info file declares the module name.");
    $yaml_tester->assertPropertyHasValue('description', $module_data['short_description'], "The info file declares the module description.");
    $yaml_tester->assertPropertyHasValue('core', "8.x", "The info file declares the core version.");
    $yaml_tester->assertPropertyHasValue('configure', "admin/config/system/$module_name", "The info file declares the configuration path.");
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
      'settings_form' => [
        0 => [
          'parent_route' => 'system.admin_config_system',
        ],
      ],
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer testmodule', "The permissions file declares the admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer testmodule', 'title'], 'Administer testmodule', "The permission has the expected title.");
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
       'settings_form' => [
        0 => [
          'parent_route' => 'system.admin_config_system',
        ],
      ],
       'router_items' => array(
          0 => [
            'path' => 'requested/route/path',
          ],
       ),
       'readme' => FALSE,
     );
     $files = $this->generateModuleFiles($module_data);

     // Check the .routing file.
     $routing_file = $files["$module_name.routing.yml"];
     $yaml_tester = new YamlTester($routing_file);

     $expected_route_name = 'testmodule.settings';
     $yaml_tester->assertHasProperty($expected_route_name, "The routing file has the property for the admin route.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'path'], '/admin/config/system/testmodule', "The routing file declares the route path.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_form'], '\Drupal\testmodule\Form\AdminSettingsForm', "The routing file declares the route form.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_title'], 'Administer Test module', "The routing file declares the route title.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'requirements', '_permission'], 'administer testmodule', "The routing file declares the route permission.");

     $yaml_tester->assertPropertyHasValue(['testmodule.requested.route.path', 'path'], "/requested/route/path", "The routing file declares the requested path.");
  }

}
