<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests basic module generation.
 */
class ComponentModule6Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 6;

  /**
   * Test requesting a module with no options produces basic files.
   */
  function testModule6NoOptions() {
    // Create a module.
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info',
      'test_module.module',
    ], $files);

    // Check the info file.
    $info_file = $files['test_module.info'];

    $this->assertStringContainsString("name = Test module\n", $info_file);
    $this->assertStringContainsString("description = Test Module description\n", $info_file);
    $this->assertStringContainsString("core = 6.x\n", $info_file);

    // Check the module file.
    $module_file = $files["test_module.module"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);
    $php_tester->assertDrupalCodingStandards();
  }

}


