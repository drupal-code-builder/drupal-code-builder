<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for dynamic route providers.
 *
 * @group yaml
 */
class ComponentRouteCallback10Test extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected $drupalMajorVersion = 9;

  /**
   * Test generating a module info file.
   */
  public function testRouteCallback() {
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test Module',
      'dynamic_routes' => [
        0 => [
          'provider_class_short_name' => 'MyRouteProvider',
        ],
        1 => [
          'provider_class_short_name' => 'OtherRouteProvider',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
     'test_module.info.yml',
     'test_module.routing.yml',
     'src/Routing/MyRouteProvider.php',
     'src/Routing/OtherRouteProvider.php',
    ], $files);

    $routing_file = $files['test_module.routing.yml'];
    $yaml_tester = new YamlTester($routing_file);

    $yaml_tester->assertHasProperty('route_callbacks', "The routing file has the callbacks property.");
    $yaml_tester->assertPropertyHasValue(['route_callbacks', 0], '\Drupal\test_module\Routing\MyRouteProvider::routes', "The routing file declares the route path.");
    $yaml_tester->assertPropertyHasValue(['route_callbacks', 1], '\Drupal\test_module\Routing\OtherRouteProvider::routes', "The routing file declares the route path.");

    $provider_file = $files['src/Routing/MyRouteProvider.php'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $provider_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Routing\MyRouteProvider');

    $method_tester = $php_tester->getMethodTester('routes');
    $method_tester->assertMethodHasDocblockLine('Returns an array of routes.');
  }

}
