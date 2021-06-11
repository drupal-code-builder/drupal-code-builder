<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests the AdminSettingsForm generator class.
 *
 * @group form
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
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'settings_form' => [
        'parent_route' => 'system.admin_config_system',
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'src/Form/AdminSettingsForm.php',
      'test_module.routing.yml',
      'test_module.links.menu.yml',
      'test_module.permissions.yml',
      'config/schema/test_module.schema.yml',
    ], $files);

    // Check the form class code.
    $form_file = $files['src/Form/AdminSettingsForm.php'];

    $php_tester = new PHPTester($this->drupalMajorVersion, $form_file);
    $php_tester->assertDrupalCodingStandards([
      // Excluded because of the buildForm() commented-out code.
      'Drupal.Commenting.InlineComment.SpacingAfter',
      'Drupal.Commenting.InlineComment.InvalidEndChar',
    ]);
    $php_tester->assertHasClass('Drupal\test_module\Form\AdminSettingsForm');
    $php_tester->assertClassHasParent('Drupal\Core\Form\ConfigFormBase');

    $method_tester = $php_tester->getMethodTester('getFormId');
    $method_tester->assertMethodDocblockHasInheritdoc();
    $method_tester->assertReturnsString('test_module_settings_form');

    $form_builder_tester = $php_tester->getMethodTester('buildForm')->getFormBuilderTester(1);
    $form_builder_tester->assertElementCount(1);

    $php_tester->assertHasMethod('validateForm');
    $php_tester->assertHasMethod('submitForm');

    $method_tester = $php_tester->getMethodTester('getEditableConfigNames');
    $method_tester->assertMethodDocblockHasInheritdoc();
    $method_tester->assertHasNoParameters();

    // Check the schema file.
    $config_schema_file = $files['config/schema/test_module.schema.yml'];
    $yaml_tester = new YamlTester($config_schema_file);

    $yaml_tester->assertHasProperty('test_module.settings', "The schema file has the property for the settings.");
    $yaml_tester->assertPropertyHasValue(['test_module.settings', 'type'], 'config_object');
    $yaml_tester->assertPropertyHasValue(['test_module.settings', 'label'], 'Test module settings');
    $yaml_tester->assertPropertyHasValue(['test_module.settings', 'mapping'], []);

    // Check the .routing file.
    $routing_file = $files["$module_name.routing.yml"];
    $yaml_tester = new YamlTester($routing_file);

    $expected_route_name = 'test_module.settings';
    $yaml_tester->assertHasProperty($expected_route_name, "The routing file has the property for the admin route.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'path'], '/admin/config/system/test_module', "The routing file declares the route path.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_form'], '\Drupal\test_module\Form\AdminSettingsForm', "The routing file declares the route form.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_title'], 'Administer test module', "The routing file declares the route title.");
    $yaml_tester->assertPropertyHasValue([$expected_route_name, 'requirements', '_permission'], 'administer test_module', "The routing file declares the route permission.");

    // Check the menu links file.
    $links_file = $files['test_module.links.menu.yml'];
    $yaml_tester = new YamlTester($links_file);

    $yaml_tester->assertHasProperty('test_module.settings', "The links file has the link name.");
    $yaml_tester->assertPropertyHasValue(['test_module.settings', 'title'], 'Test module');
    $yaml_tester->assertPropertyHasValue(['test_module.settings', 'description'], 'Configure the settings for test module.');
    $yaml_tester->assertPropertyHasValue(['test_module.settings', 'route_name'], 'test_module.settings');
    $yaml_tester->assertPropertyHasValue(['test_module.settings', 'parent'], 'system.admin_config_system');

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer test_module', "The permissions file declares the admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer test_module', 'title'], 'Administer Test module', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer test_module', 'description'], 'Administer Test module', "The permission has the expected title.");

    // Check the .info file.
    $info_file = $files["$module_name.info.yml"];
    $yaml_tester = new YamlTester($info_file);

    $yaml_tester->assertPropertyHasValue('name', 'Test Module', "The info file declares the module name.");
    $yaml_tester->assertPropertyHasValue('description', $module_data['short_description'], "The info file declares the module description.");
    $yaml_tester->assertPropertyHasValue('core', "8.x", "The info file declares the core version.");
    $yaml_tester->assertPropertyHasValue('configure', "test_module.settings", "The info file declares the configuration route.");
  }

  /**
   * Test Admin Settings component with other permissions.
   */
  function testAdminSettingsOtherPermsTest() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'permissions' => [
        0 => [
          'permission' => 'access test_module',
        ],
      ],
      'settings_form' => [
        'parent_route' => 'system.admin_config_system',
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer test_module', "The permissions file declares the admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer test_module', 'title'], 'Administer Test module', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer test_module', 'description'], 'Administer Test module', "The permission has the expected title.");

    $yaml_tester->assertHasProperty('access test_module', "The permissions file declares the requested permission.");
   }

   /**
    * Test Admin Settings component with other router items.
    */
   function testAdminSettingsOtherRouterItemsTest() {
     // Create a module.
     $module_name = 'test_module';
     $module_data = [
       'base' => 'module',
       'root_name' => $module_name,
       'readable_name' => 'Test module',
       'short_description' => 'Test Module description',
       'hooks' => [
       ],
       'permissions' => [
         0 => [
           'permission' => 'access test_module',
         ],
       ],
       'settings_form' => [
          'parent_route' => 'system.admin_config_system',
        ],
       'router_items' => [
          0 => [
            'path' => '/requested/route/path',
            'controller' => [
              'controller_type' => 'controller',
            ],
            'access' => [
              'access_type' => 'permission',
            ],
          ],
       ],
       'readme' => FALSE,
     ];
     $files = $this->generateModuleFiles($module_data);

     // Check the .routing file.
     $routing_file = $files["$module_name.routing.yml"];
     $yaml_tester = new YamlTester($routing_file);

     $expected_route_name = 'test_module.settings';
     $yaml_tester->assertHasProperty($expected_route_name, "The routing file has the property for the admin route.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'path'], '/admin/config/system/test_module', "The routing file declares the route path.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_form'], '\Drupal\test_module\Form\AdminSettingsForm', "The routing file declares the route form.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'defaults', '_title'], 'Administer test module', "The routing file declares the route title.");
     $yaml_tester->assertPropertyHasValue([$expected_route_name, 'requirements', '_permission'], 'administer test_module', "The routing file declares the route permission.");

     $yaml_tester->assertPropertyHasValue(['test_module.requested.route.path', 'path'], "/requested/route/path", "The routing file declares the requested path.");
  }

}
