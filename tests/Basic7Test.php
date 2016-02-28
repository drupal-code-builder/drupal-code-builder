<?php

/**
 * @file
 * Contains Basic7Test.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/DrupalCodeBuilderTestBase.php';

/**
 * Basic test class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/Basic7Test.php
 * @endcode
 */
class Basic7Test extends DrupalCodeBuilderTestBase {

  /**
   * Test the hook data is reported correctly.
   */
  public function testReportHookData() {
    $this->setupDrupalCodeBuilder(7);

    $hooks_directory = \DrupalCodeBuilder\Factory::getEnvironment()->getHooksDirectory();

    $mb_task_handler_report = \DrupalCodeBuilder\Factory::getTask('ReportHookData');
    $this->assertTrue(is_object($mb_task_handler_report), "A task handler object was returned.");

    $hook_groups = $mb_task_handler_report->listHookData();
    $this->assertTrue(is_array($hook_groups) || !empty($hook_groups), "An non-empty array of hook data was returned.");
  }

  /**
   * Test build request functionality.
   */
  function testModuleGenerationHooksLimitedBuild() {
    // Create a module, specifying limited build.
    // It is crucial to create a new module name, as we eval() the generated
    // code!
    $module_name = 'testmodule2';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
        // These two hooks will go in the .module file.
        'hook_menu',
        'hook_block_info',
        // This goes in a tokens.inc file, and also has complex parameters.
        'hook_tokens',
        // This goes in the .install file.
        'hook_install',
      ),
      'requested_build' => array(
        'install' => TRUE,
      ),
      // Override the default value for this.
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertEquals(count($files), 1, "Only one file is returned.");

    // Check the .install file.
    $install_file = $files["$module_name.install"];

    $this->assertWellFormedPHP($install_file, "Install file parses as well-formed PHP.");

    $this->assertFileHeader($install_file, "The install file contains the correct PHP open tag and file doc header");

    $this->assertHookDocblock($install_file, 'hook_install', "The install file contains the docblock for hook_install().");
    $this->assertHookImplementation($install_file, 'hook_install', $module_name, "The instal file contains a function declaration that implements hook_install().");
  }

}
