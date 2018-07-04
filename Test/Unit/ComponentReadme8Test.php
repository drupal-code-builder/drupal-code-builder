<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the README file.
 */
class ComponentReadme8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Tests the README file.
   */
  function test8ReadmeFile() {
    // Create a module.
    $module_data = array(
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'readme' => TRUE,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "One file is returned.");

    $this->assertFiles([
      'test_module.info.yml',
      'README.txt',
    ], $files);

    $readme_file = $files['README.txt'];

    $this->assertContains("Test module", $readme_file);
    $this->assertContains("TODO: write some documentation.", $readme_file);
  }

}
