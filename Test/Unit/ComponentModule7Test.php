<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests basic module generation.
 *
 * @group hooks
 */
class ComponentModule7Test extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected $drupalMajorVersion = 7;

  /**
   * Test requesting a module with no options produces basic files.
   */
  function testNoOptions() {
    // Create a module.
    $module_name = 'testmodule';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Two files are returned.");

    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.info", $files, "The files list has a .info file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);
    $php_tester->assertDrupalCodingStandards();
  }

  /**
   * Test the helptext option produces hook_help().
   */
  function testHelptextOption() {
    // Create a module.
    $module_name = 'testmodule';
    $help_text = 'This is the test help text';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'readme' => FALSE,
      'module_help_text' => $help_text,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Two files are returned.");

    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.info", $files, "The files list has a .info file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);

    $php_tester->assertDrupalCodingStandards();

    $php_tester->assertHasHookImplementation('hook_help', $module_name);

    $this->assertFunctionCode($module_file, $module_name . '_help', $help_text, "The hook_help() implementation contains the requested help text.");
  }

}
