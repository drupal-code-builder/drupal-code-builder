<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests for Hooks component.
 *
 * @group hooks
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

    $php_tester = new PHPTester($module_file);
    // Exclude the sniff for blank lines after a comment, as we intentionally
    // have one in our template for hook_menu().
    $php_tester->assertDrupalCodingStandards(['Drupal.Commenting.InlineComment.SpacingAfter']);

    $php_tester->assertHasHookImplementation('hook_menu', $module_name);
    $php_tester->assertHasHookImplementation('hook_block_info', $module_name);

    $php_tester->assertHasNotHookImplementation('hook_install', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_tokens', $module_name);

    // Check the .install file.
    $install_file = $files["$module_name.install"];
    $php_tester = new PHPTester($install_file);
    $php_tester->assertDrupalCodingStandards();

    $php_tester->assertHasHookImplementation('hook_install', $module_name);

    $php_tester->assertHasNotHookImplementation('hook_menu', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_block_info', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_tokens', $module_name);

    // Check the .tokens.inc file.
    $tokens_file = $files["$module_name.tokens.inc"];

    $php_tester = new PHPTester($tokens_file);
    $php_tester->assertDrupalCodingStandards();

    $php_tester->assertHasHookImplementation('hook_tokens', $module_name);

    $php_tester->assertHasNotHookImplementation('hook_menu', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_block_info', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_install', $module_name);

    // Check the .info file.
    $info_file = $files["$module_name.info"];

    $this->assertNoTrailingWhitespace($info_file, "The info file contains no trailing whitespace.");
    $this->assertInfoLine($info_file, 'name', $module_data['readable_name'], "The info file declares the module name.");
    $this->assertInfoLine($info_file, 'description', $module_data['short_description'], "The info file declares the module description.");
    $this->assertInfoLine($info_file, 'core', "7.x", "The info file declares the core version.");
  }

}
