<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Fixtures\File\MockableExtension;
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
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => [
        'hook_help',
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $module_file = $files["$module_name.module"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertFileDocblockHasLine("Contains hook implementations for the Test Module module.");
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
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => [
        // These hooks will go in the .module file.
        'hook_help',
        'hook_form_alter',
        // This goes in a tokens.inc file, and also has complex parameters.
        'hook_tokens',
        // This goes in the .install file.
        'hook_install',
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(4, $files, "The expected number of files are returned.");

    $file_names = array_keys($files);

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.tokens.inc", $files, "The files list has a .tokens.inc file.");
    $this->assertArrayHasKey("$module_name.install", $files, "The files list has a .install file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);
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

    $php_tester = new PHPTester($this->drupalMajorVersion, $install_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasHookImplementation('hook_install', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_help', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_form_alter', $module_name);
    $php_tester->assertHasNotHookImplementation('hook_tokens', $module_name);

    // Check the .tokens.inc file.
    $tokens_file = $files["$module_name.tokens.inc"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $tokens_file);
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

  /**
   * Tests with an existing code file.
   *
   * @group existing
   */
  public function testHooksWithExitingFunctions() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => [
        'hook_block_view_alter',
        'hook_element_info_alter',
      ],
      'readme' => FALSE,
    ];

    $extension = new MockableExtension('module', __DIR__ . '/../Fixtures/modules/existing/');

    // This includes:
    //  - an existing function
    //  - an import which will not also be generated
    //  - an import which will also be in the generated code
    $existing_module_file = <<<'EOPHP'
      <?php

      /**
       * @file
       * Contains hook implementations for the Test Module module.
       */

      use Drupal\Core\Block\BlockPluginInterface;
      use QualifiedNamespace\ShortClassName;

      /**
       * Does a thing.
       */
      function existing_function() {
        // Code does a thing.
        $foo = new ShortClassName();
      }

      /**
       * Implements hook_element_info_alter().
       */
      function test_module_element_info_alter(array &$info) {
        // Existing hook implementation code.
        $info = [];
      }

      EOPHP;

    $extension->setFile('%module.module', $existing_module_file);

    $files = $this->generateModuleFiles($module_data, $extension);
    $module_file = $files['test_module.module'];

    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);

    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertImportsClassLike(['Drupal\Core\Block\BlockPluginInterface']);
    $php_tester->assertImportsClassLike(['QualifiedNamespace\ShortClassName']);
    $php_tester->assertHasHookImplementation('hook_block_view_alter', $module_name);
    $php_tester->assertHasHookImplementation('hook_element_info_alter', $module_name);
    $php_tester->assertHasFunction('existing_function');

    // The existing hook implementation is overwritten.
    $this->assertStringNotContainsString('Existing hook implementation code', $module_file);
  }

}
