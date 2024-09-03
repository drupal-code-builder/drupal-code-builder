<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Fixtures\File\MockableExtension;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for Hooks component on Drupal 11.
 *
 * @group hooks
 */
class ComponentHooks11Test extends TestBase {

  /**
   * The PHP CodeSniffer snifs to exclude for this test.
   *
   * @var string[]
   */
  // TODO move this, not everything needs it!
  static protected $phpcsExcludedSniffs = [
    // Temporarily exclude the sniff for comment lines being too long, as a
    // comment in hook_form_alter() violates this.
    // TODO: remove this when https://www.drupal.org/project/drupal/issues/2924184
    // is fixed.
    'Drupal.Files.LineLength.TooLong',
    // Temporarily exclude the sniff for array lines being too long, as code in
    // hook_theme() violates this.
    // TODO: remove this when https://www.drupal.org/project/drupal/issues/3471544
    // is fixed.
    'Drupal.Arrays.Array.LongLineDeclaration',
  ];

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 11;

  /**
   * Tests procedural hooks can also be generated on 11.
   */
  public function testHookImplementationTypeProcedural() {
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hook_implementation_type' => 'procedural',
      'hooks' => [
        'hook_block_access',
        'hook_form_alter',
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.module',
    ], $files);
  }

  /**
   * Tests generating OO hooks.
   */
  public function testOOHooks() {
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hook_implementation_type' => 'oo',
      'hooks' => [
        'hook_block_access',
        'hook_form_alter',
        'hook_install',
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.install',
      'src/Hooks/TestModuleHooks.php',
    ], $files);

    $hooks_file = $files['src/Hooks/TestModuleHooks.php'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $hooks_file);
    $php_tester->assertDrupalCodingStandards(static::$phpcsExcludedSniffs);

    $php_tester->assertHasClass('Drupal\test_module\Hooks\TestModuleHooks');
    $php_tester->assertHasMethod('formAlter');
    $php_tester->assertHasMethod('blockAccess');
    $php_tester->assertNotHasMethod('install');

    // TODO: Attribute testing.
    $this->assertStringContainsString('#[Hook("form_alter")]', $hooks_file);
    $this->assertStringContainsString('#[Hook("block_access")]', $hooks_file);

    // Check the .install file has a procedural implementation for
    // hook_install().
    $install_file = $files['test_module.install'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $install_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertFileDocblockHasLine("Contains install and update hooks for the Test Module module.");
    $php_tester->assertHasHookImplementation('hook_install', $module_name);
  }

  public function testHookImplementationLegacy() {
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hook_implementation_type' => 'oo_legacy',
      'hooks' => [
        'hook_block_access',
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.module',
      'test_module.services.yml',
      'src/Hooks/TestModuleHooks.php',
    ], $files);

    dump($files);

  }

  // TODO: test merging of services.yml!

}
