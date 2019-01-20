<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for Router item component.
 *
 * @group yaml
 */
class ComponentRouterItem8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test generating a module with routes.
   */
  public function testRouteGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => [
        0 => [
          'path' => '/my/path',
          'controller_type' => 'controller',
          'access_type' => 'permission',
        ],
        1 => [
          'path' => '/my/other-path',
          'title' => 'My Other Page',
          'controller_type' => 'controller',
          'access_type' => 'permission',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(4, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.routing.yml", $files, "The files list has a routing file.");
    $this->assertArrayHasKey("src/Controller/MyPathController.php", $files, "The files list has a controller class file.");
    $this->assertArrayHasKey("src/Controller/MyOtherPathController.php", $files, "The files list has a controller class file.");

    $routing_file = $files["$module_name.routing.yml"];
    $yaml_tester = new YamlTester($routing_file);

    $yaml_tester->assertHasProperty('test_module.my.path', "The routing file has the property for the route.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'path'], '/my/path', "The routing file declares the route path.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'defaults', '_controller'], '\Drupal\test_module\Controller\MyPathController::content', "The routing file declares the route controller.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'defaults', '_title'], 'myPage', "The routing file declares the route title.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'requirements', '_permission'], 'TODO: set permission machine name', "The routing file declares the route permission.");

    $yaml_tester->assertHasProperty('test_module.my.other-path', "The routing file has the property for the route.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.other-path', 'path'], '/my/other-path', "The routing file declares the route path.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.other-path', 'defaults', '_controller'], '\Drupal\test_module\Controller\MyOtherPathController::content', "The routing file declares the route controller.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.other-path', 'defaults', '_title'], 'My Other Page', "The routing file declares the route title.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.other-path', 'requirements', '_permission'], 'TODO: set permission machine name', "The routing file declares the route permission.");

    $controller_file = $files["src/Controller/MyPathController.php"];

    $php_tester = new PHPTester($controller_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass("Drupal\\{$module_name}\Controller\MyPathController");
    $php_tester->assertHasMethod('content');
  }

  /**
   * Test generating a route with a menu link.
   */
  public function testRouteGenerationWithMenuLink() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => [
        0 => [
          'path' => '/my/path',
          'controller_type' => 'controller',
          'access_type' => 'permission',
          'title' => 'My Page',
          'menu_link' => [
            0 => [
              'title' => 'My link',
            ],
          ],
        ],
        1 => [
          'path' => 'my/other-path',
          'controller_type' => 'controller',
          'access_type' => 'permission',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(5, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.routing.yml", $files, "The files list has a routing file.");
    $this->assertArrayHasKey("src/Controller/MyPathController.php", $files, "The files list has a controller class file.");
    $this->assertArrayHasKey("src/Controller/MyOtherPathController.php", $files, "The files list has a controller class file.");
    $this->assertArrayHasKey("$module_name.links.menu.yml", $files, "The files list has a menu links file.");

    $menu_links_file = $files["$module_name.links.menu.yml"];
    $yaml_tester = new YamlTester($menu_links_file);

    $yaml_tester->assertHasProperty('test_module.my.path', "The menu links file has the property for the menu link.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'title'], 'My link', "The menu links file declares the link title.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'route_name'], 'test_module.my.path', "The menu links file declares the link route.");
  }

}
