<?php

/**
 * @file
 * Contains ComponentAPI8Test.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/DrupalCodeBuilderTestBase.php';

/**
 * Tests for API component.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/ComponentAPI8Test.php
 * @endcode
 */
class ComponentAPI8Test extends DrupalCodeBuilderTestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test generating a module with an api.php file.
   */
  public function testModuleGenerationApiFile() {
    $mb_task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    $this->assertTrue(is_object($mb_task_handler_generate), "A task handler object was returned.");

    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'readme' => FALSE,
      'api' => TRUE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "Two files are returned.");

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.api.php", $files, "The files list has a .tokens.inc file.");

    $api_file = $files["$module_name.api.php"];
    $this->assertNoTrailingWhitespace($api_file);
    $this->assertFileHeader($api_file);

    // TODO: expand the docblock assertion for these.
    $this->assertContains("Hooks provided by the Test Module module.", $api_file, 'The API file contains the correct docblock header.');
    $this->assertContains("@addtogroup hooks", $api_file, 'The API file contains the addtogroup docblock tag.');
  }

}
