<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for Router item component on D10.
 */
#[Group('yaml')]
class ComponentRouterItem10Test extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected $drupalMajorVersion = 10;

  /**
   * Test generating a module with routes.
   */
  public function testBasicRouteGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => [
        [
          'path' => '/my/path',
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

    $this->assertFiles([
      "$module_name.info.yml",
      "$module_name.routing.yml",
      "src/Controller/MyPathController.php",
    ], $files);

    $routing_file = $files["$module_name.routing.yml"];
    $yaml_tester = new YamlTester($routing_file);

    $yaml_tester->assertHasProperty('test_module.my.path', "The routing file has the property for the route.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'path'], '/my/path', "The routing file declares the route path.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'defaults', '_controller'], '\Drupal\test_module\Controller\MyPathController::content', "The routing file declares the route controller.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'defaults', '_title'], 'myPage', "The routing file declares the route title.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'requirements', '_permission'], 'access content', "The routing file declares the route permission.");

    $controller_file = $files["src/Controller/MyPathController.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $controller_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass("Drupal\\{$module_name}\Controller\MyPathController");

    $method_tester = $php_tester->getMethodTester('content');
    $method_tester->getDocBlockTester()->assertHasLine('Callback for the test_module.my.path route.');
  }

}
