<?php

/**
 * @file
 * Contains ModuleRequestedBuildTest.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/DrupalCodeBuilderTestBase.php';

/**
 * Test the functionality for requesting only certain files to be generated.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/ModuleRequestedBuildTest.php
 * @endcode
 */
class ModuleRequestedBuildTest extends DrupalCodeBuilderTestBase {

  /**
   * Test build request functionality.
   */
  function testModuleGenerationBuildRequest() {
    $this->setupDrupalCodeBuilder(7);

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

    $this->assertCount(1, $files, "Only one file is returned.");

    // Check the .install file.
    $install_file = $files["$module_name.install"];

    $this->assertWellFormedPHP($install_file, "Install file parses as well-formed PHP.");

    $this->assertFileHeader($install_file, "The install file contains the correct PHP open tag and file doc header");

    $this->assertHookDocblock($install_file, 'hook_install', "The install file contains the docblock for hook_install().");
    $this->assertHookImplementation($install_file, 'hook_install', $module_name, "The instal file contains a function declaration that implements hook_install().");
  }

}
