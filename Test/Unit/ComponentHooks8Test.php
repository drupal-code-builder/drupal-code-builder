<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests for Hooks component.
 */
class ComponentHooks8Test extends TestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test generating a module with hooks in various files.
   */
  public function testModuleGenerationHooks() {
    $mb_task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    $this->assertTrue(is_object($mb_task_handler_generate), "A task handler object was returned.");

    // Assemble module data.
    // Note the module name must be unique across all tests, as
    // assertWellFormedPHP() uses eval() on module code files.
    $module_name = 'testmodule_8';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => array(
        // These hooks will go in the .module file.
        'hook_help',
        'hook_form_alter',
        // This goes in a tokens.inc file, and also has complex parameters.
        'hook_tokens',
        // This goes in the .install file.
        'hook_install',
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(4, $files, "The expected number of files are returned.");

    $file_names = array_keys($files);

    $this->assertContains("$module_name.info.yml", $file_names, "The files list has a .info file.");
    $this->assertContains("$module_name.module", $file_names, "The files list has a .module file.");
    $this->assertContains("$module_name.tokens.inc", $file_names, "The files list has a .tokens.inc file.");
    $this->assertContains("$module_name.install", $file_names, "The files list has a .install file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    //debug($module_file);

    $this->assertNoTrailingWhitespace($module_file, "The module file contains no trailing whitespace.");

    $this->assertWellFormedPHP($module_file, "Module file parses as well-formed PHP.");

    $this->assertFileHeader($module_file, "The module file contains the correct PHP open tag and file doc header");

    $this->assertHookDocblock('hook_help', $module_file, "The module file contains the docblock for hook_block_info().");
    $this->assertHookImplementation($module_file, 'hook_help', $module_name, "The module file contains a function declaration that implements hook_block_info().");

    $this->assertHookDocblock('hook_form_alter', $module_file, "The module file contains the docblock for hook_menu().");
    $this->assertHookImplementation($module_file, 'hook_form_alter', $module_name, "The module file contains a function declaration that implements hook_menu().");

    $this->assertNoHookDocblock('hook_install', $module_file, "The module file does not contain the docblock for hook_install().");

    // Check the .install file.
    $install_file = $files["$module_name.install"];

    $this->assertWellFormedPHP($install_file);
    $this->assertNoTrailingWhitespace($install_file, "The install file contains no trailing whitespace.");

    $this->assertWellFormedPHP($install_file, "Install file parses as well-formed PHP.");

    $this->assertFileHeader($install_file, "The install file contains the correct PHP open tag and file doc header");

    $this->assertHookDocblock('hook_install', $install_file, "The install file contains the docblock for hook_install().");
    $this->assertHookImplementation($install_file, 'hook_install', $module_name, "The instal file contains a function declaration that implements hook_install().");

    $this->assertNoHookDocblock('hook_help', $install_file, "The install file does not contain the docblock for hook_menu().");
    $this->assertNoHookDocblock('hook_form_alter', $install_file, "The install file does not contain the docblock for hook_block_info().");

    // Check the .tokens.inc file.
    $tokens_file = $files["$module_name.tokens.inc"];

    $this->assertWellFormedPHP($tokens_file);
    $this->assertNoTrailingWhitespace($tokens_file, "The tokens file contains no trailing whitespace.");
    $this->assertHookDocblock('hook_tokens', $tokens_file, "The tokens file contains the docblock for hook_tokens().");
    $this->assertHookImplementation($tokens_file, 'hook_tokens', $module_name, "The tokens file contains a function declaration that implements hook_tokens().");

    // Check the .info file.
    $info_file = $files["$module_name.info.yml"];

    $this->assertNoTrailingWhitespace($info_file, "The info file contains no trailing whitespace.");
    $this->assertYamlProperty($info_file, 'name', $module_data['readable_name'], "The info file declares the module name.");
    $this->assertYamlProperty($info_file, 'description', $module_data['short_description'], "The info file declares the module description.");
    $this->assertYamlProperty($info_file, 'core', "8.x", "The info file declares the core version.");
  }

}
