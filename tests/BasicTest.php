<?php

/**
 * @file
 * Contains BasicTest.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/ModuleBuilderTestBase.php';

/**
 * Basic test class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/BasicTest.php
 * @endcode
 */
class BasicTest extends ModuleBuilderTestBase {

  /**
   * Test the hook data is reported correctly.
   */
  public function testReportHookData() {
    $this->setupModuleBuilder(7);

    $hooks_directory = \ModuleBuilder\Factory::getEnvironment()->getHooksDirectory();

    $mb_task_handler_report = \ModuleBuilder\Factory::getTask('ReportHookData');
    $this->assertTrue(is_object($mb_task_handler_report), "A task handler object was returned.");

    $hook_groups = $mb_task_handler_report->listHookData();
    $this->assertTrue(is_array($hook_groups) || !empty($hook_groups), "An non-empty array of hook data was returned.");
  }

  /**
   * Test generating a module with hooks in various files.
   */
  public function testModuleGenerationHooks() {
    $this->setupModuleBuilder(7);

    $mb_task_handler_generate = \ModuleBuilder\Factory::getTask('Generate', 'module');
    $this->assertTrue(is_object($mb_task_handler_generate), "A task handler object was returned.");

    // Assemble module data.
    $module_name = 'testmodule';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => array(
        // These two hooks will go in the .module file.
        'hook_menu',
        'hook_node_info',
        // This goes in a tokens.inc file, and also has complex parameters.
        'hook_tokens',
        // This goes in the .install file.
        'hook_install',
      ),
      'requested_build' => array(
        'all' => TRUE,
      ),
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertTrue(isset($files["$module_name.module"]), "The files list has a .module file.");
    $this->assertTrue(isset($files["$module_name.install"]), "The files list has a .install file.");
    $this->assertTrue(isset($files["$module_name.info"]), "The files list has a .info file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    //debug($module_file);

    $this->assertNoTrailingWhitespace($module_file, "The module file contains no trailing whitespace.");

    $this->assertWellFormedPHP($module_file, "Module file parses as well-formed PHP.");

    $this->assertFileHeader($module_file, "The module file contains the correct PHP open tag and file doc header");

    $this->assertHookDocblock($module_file, 'hook_menu', "The module file contains the docblock for hook_menu().");
    $this->assertHookImplementation($module_file, 'hook_menu', $module_name, "The module file contains a function declaration that implements hook_menu().");

    $this->assertHookDocblock($module_file, 'hook_node_info', "The module file contains the docblock for hook_node_info().");
    $this->assertHookImplementation($module_file, 'hook_node_info', $module_name, "The module file contains a function declaration that implements hook_node_info().");

    $this->assertNoHookDocblock($module_file, 'hook_install', "The module file does not contain the docblock for hook_install().");

    // Check the .install file.
    $install_file = $files["$module_name.install"];

    $this->assertNoTrailingWhitespace($install_file, "The install file contains no trailing whitespace.");

    $this->assertWellFormedPHP($install_file, "Install file parses as well-formed PHP.");

    $this->assertFileHeader($install_file, "The install file contains the correct PHP open tag and file doc header");

    $this->assertHookDocblock($install_file, 'hook_install', "The install file contains the docblock for hook_install().");
    $this->assertHookImplementation($install_file, 'hook_install', $module_name, "The instal file contains a function declaration that implements hook_install().");

    $this->assertNoHookDocblock($install_file, 'hook_menu', "The install file does not contain the docblock for hook_menu().");
    $this->assertNoHookDocblock($install_file, 'hook_node_info', "The install file does not contain the docblock for hook_node_info().");

    // Check the .tokens.inc file.
    $tokens_file = $files["$module_name.tokens.inc"];

    $this->assertNoTrailingWhitespace($tokens_file, "The tokens file contains no trailing whitespace.");
    $this->assertHookDocblock($tokens_file, 'hook_tokens', "The tokens file contains the docblock for hook_tokens().");
    $this->assertHookImplementation($tokens_file, 'hook_tokens', $module_name, "The tokens file contains a function declaration that implements hook_tokens().");

    // Check the .info file.
    $info_file = $files["$module_name.info"];

    $this->assertNoTrailingWhitespace($info_file, "The info file contains no trailing whitespace.");
    $this->assertInfoLine($info_file, 'name', $module_data['readable_name'], "The info file declares the module name.");
    $this->assertInfoLine($info_file, 'description', $module_data['short_description'], "The info file declares the module description.");
    $this->assertInfoLine($info_file, 'core', "7.x", "The info file declares the core version.");
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
        'hook_node_info',
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
