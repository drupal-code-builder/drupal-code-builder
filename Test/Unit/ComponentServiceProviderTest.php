<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests for Service provider component.
 */
class ComponentServiceProviderTest extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test generating a module with a service.
   */
  public function testBasicServiceGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'service_provider' => TRUE,
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'src/TestModuleServiceProvider.php',
    ], $files);

    $service_provider_file = $files['src/TestModuleServiceProvider.php'];

    $php_tester = new PHPTester($this->drupalMajorVersion, $service_provider_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\TestModuleServiceProvider');
    $php_tester->assertClassHasParent('Drupal\Core\DependencyInjection\ServiceProviderBase');
    $php_tester->assertHasMethods(['alter']);
  }

}