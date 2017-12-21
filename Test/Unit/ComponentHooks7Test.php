<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests for Hooks component.
 */
class ComponentHooks7Test extends TestBaseComponentGeneration {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(7);
  }

  /**
   * Test generating a module with hooks in various files.
   */
  public function testModuleGenerationHooks() {
    $mb_task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
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
        'hook_block_info',
        // This goes in a tokens.inc file, and also has complex parameters.
        'hook_tokens',
        // This goes in the .install file.
        'hook_install',
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(4, $files, "Four files are returned.");

    $file_names = array_keys($files);

    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.tokens.inc", $files, "The files list has a .tokens.inc file.");
    $this->assertArrayHasKey("$module_name.install", $files, "The files list has a .install file.");
    $this->assertArrayHasKey("$module_name.info", $files, "The files list has a .info file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    //debug($module_file);

    $this->assertNoTrailingWhitespace($module_file, "The module file contains no trailing whitespace.");

    $this->assertWellFormedPHP($module_file, "Module file parses as well-formed PHP.");

    $this->assertFileHeader($module_file, "The module file contains the correct PHP open tag and file doc header");

    $this->assertHookDocblock('hook_menu', $module_file, "The module file contains the docblock for hook_menu().");
    $this->assertHookImplementation($module_file, 'hook_menu', $module_name, "The module file contains a function declaration that implements hook_menu().");

    $this->assertHookDocblock('hook_block_info', $module_file, "The module file contains the docblock for hook_block_info().");
    $this->assertHookImplementation($module_file, 'hook_block_info', $module_name, "The module file contains a function declaration that implements hook_block_info().");

    $this->assertNoHookDocblock('hook_install', $module_file, "The module file does not contain the docblock for hook_install().");

    // Check the .install file.
    $install_file = $files["$module_name.install"];

    $this->assertNoTrailingWhitespace($install_file, "The install file contains no trailing whitespace.");

    $this->assertWellFormedPHP($install_file, "Install file parses as well-formed PHP.");

    $this->assertFileHeader($install_file, "The install file contains the correct PHP open tag and file doc header");

    $this->assertHookDocblock('hook_install', $install_file, "The install file contains the docblock for hook_install().");
    $this->assertHookImplementation($install_file, 'hook_install', $module_name, "The instal file contains a function declaration that implements hook_install().");

    $this->assertNoHookDocblock('hook_menu', $install_file, "The install file does not contain the docblock for hook_menu().");
    $this->assertNoHookDocblock('hook_block_info', $install_file, "The install file does not contain the docblock for hook_block_info().");

    // Check the .tokens.inc file.
    $tokens_file = $files["$module_name.tokens.inc"];

    $this->assertWellFormedPHP($tokens_file);
    $this->assertNoTrailingWhitespace($tokens_file, "The tokens file contains no trailing whitespace.");
    $this->assertHookDocblock('hook_tokens', $tokens_file, "The tokens file contains the docblock for hook_tokens().");
    $this->assertHookImplementation($tokens_file, 'hook_tokens', $module_name, "The tokens file contains a function declaration that implements hook_tokens().");

    // Check the .info file.
    $info_file = $files["$module_name.info"];

    $this->assertNoTrailingWhitespace($info_file, "The info file contains no trailing whitespace.");
    $this->assertInfoLine($info_file, 'name', $module_data['readable_name'], "The info file declares the module name.");
    $this->assertInfoLine($info_file, 'description', $module_data['short_description'], "The info file declares the module description.");
    $this->assertInfoLine($info_file, 'core', "7.x", "The info file declares the core version.");
  }

}
