<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for Hooks component.
 *
 * @group hooks
 */
class ComponentHooks8Test extends TestBase {

  /**
   * The PHP CodeSniffer snifs to exclude for this test.
   *
   * @var string[]
   */
  static protected $phpcsExcludedSniffs = [
    // Temporarily exclude the sniff for comment lines being too long, as a
    // comment in hook_form_alter() violates this.
    // TODO: remove this when https://www.drupal.org/project/drupal/issues/2924184
    // is fixed.
    'Drupal.Files.LineLength.TooLong',
  ];

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Tests generating a single hook implementation.
   *
   * Useful for debugging when generating multiple hooks creates too much noise.
   */
  public function testSingleHook8() {
    $module_name = 'testmodule_8';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => array(
        'hook_help',
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $module_file = $files["$module_name.module"];

    $php_tester = new PHPTester($module_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasHookImplementation('hook_help', $module_name);
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

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.tokens.inc", $files, "The files list has a .tokens.inc file.");
    $this->assertArrayHasKey("$module_name.install", $files, "The files list has a .install file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];

    $php_tester = new PHPTester($module_file);
    $phpcs_excluded_sniffs = [
      // Temporarily exclude the sniff for comment lines being too long, as a
      // comment in hook_form_alter() violates this.
      // TODO: remove this when https://www.drupal.org/project/drupal/issues/2924184
      // is fixed.
      'Drupal.Files.LineLength.TooLong',
    ];
    $php_tester->assertDrupalCodingStandards($phpcs_excluded_sniffs);
    $php_tester->assertHasHookImplementation('hook_help', $module_name);
    $php_tester->assertHasHookImplementation('hook_form_alter', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_install', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_tokens', $module_name);

    // Check the .install file.
    $install_file = $files["$module_name.install"];

    $php_tester = new PHPTester($install_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasHookImplementation('hook_install', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_help', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_form_alter', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_tokens', $module_name);

    // Check the .tokens.inc file.
    $tokens_file = $files["$module_name.tokens.inc"];

    $php_tester = new PHPTester($tokens_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasHookImplementation('hook_tokens', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_help', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_form_alter', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_install', $module_name);

    // Check the .info file.
    $info_file = $files["$module_name.info.yml"];

    $yaml_tester = new YamlTester($info_file);
    $yaml_tester->assertPropertyHasValue('name', $module_data['readable_name']);
    $yaml_tester->assertPropertyHasValue('description', $module_data['short_description']);
    $yaml_tester->assertPropertyHasValue('core', '8.x');
  }

}
