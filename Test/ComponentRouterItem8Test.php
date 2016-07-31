<?php

/**
 * @file
 * Contains ComponentRouterItem8Test.
 */

namespace DrupalCodeBuilder\Test;

/**
 * Tests for Router item component.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/ComponentRouterItem8Test.php
 * @endcode
 */
class ComponentRouterItem8Test extends TestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

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
      'router_items' => array(
        0 => 'my/path',
        1 => 'my/other-path',
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(4, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.routing.yml", $files, "The files list has a routing file.");
    $this->assertArrayHasKey("src/Controller/MyPathController.php", $files, "The files list has a controller class file.");
    $this->assertArrayHasKey("src/Controller/MyOtherPathController.php", $files, "The files list has a controller class file.");

    $routing_file = $files["$module_name.routing.yml"];

    $this->assertYamlProperty($routing_file, 'test_module.my.path', NULL, "The routing file has the property for the route.");
    $this->assertYamlProperty($routing_file, 'path', '/my/path', "The routing file declares the route path.");
    $this->assertYamlProperty($routing_file, '_controller', '\Drupal\test_module\Controller\MyPathController::content', "The routing file declares the route controller.");

    $controller_file = $files["src/Controller/MyPathController.php"];

    $this->assertNoTrailingWhitespace($controller_file, "The controller file contains no trailing whitespace.");
    $this->assertClassFileFormatting($controller_file);

    $this->assertNamespace(['Drupal', $module_name, 'Controller'], $controller_file, "The controller file contains contains the expected namespace.");
    $this->assertClass('MyPathController', $controller_file, "The controller file contains the controller class.");
  }

}
