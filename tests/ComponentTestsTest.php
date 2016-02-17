<?php

/**
 * @file
 * Contains ComponentTestsTest.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/ModuleBuilderTestBase.php';

/**
 * Basic test class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/ComponentTestsTest.php
 * @endcode
 */
class ComponentTestsTest extends ModuleBuilderTestBase {

  /**
   * Test Tests component.
   */
  function testModuleGenerationTests() {
    $this->setupModuleBuilder(7);

    // Create a module.
    $module_name = 'testmodule';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'requested_components' => array(
        'tests' => 'tests',
        //'info' => 'info',
      ),
      'requested_build' => array(
        'tests' => TRUE,
        //'info' => TRUE,
      ),
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertEquals(count($files), 1, "Only one file is returned.");

    // Check the .test file.
    $tests_file = $files["tests/$module_name.test"];

    // Can't use assertWellFormedPHP() as the parent class does not exist here.

    $this->assertFileHeader($tests_file, "The install file contains the correct PHP open tag and file doc header");

    // TODO: check this contains the test case class.
  }

}