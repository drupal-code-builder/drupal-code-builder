<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the README file.
 */
class ComponentReadme10Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 10;

  /**
   * Tests the README file.
   */
  function test8ReadmeFile() {
    // Create a module.
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'readme' => TRUE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'README.md',
    ], $files);

    $readme_file = $files['README.md'];

    $this->assertStringContainsString("# Test module", $readme_file);
    $this->assertStringContainsString("TODO: write some documentation.", $readme_file);
  }

}
