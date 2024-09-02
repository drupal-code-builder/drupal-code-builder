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
  protected $drupalMajorVersion = 11;

  /**
   * Tests generating OO hooks
   */
  public function testOOHooks() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => [
        'hook_theme',
        'hook_form_alter',
      ],
      'readme' => FALSE,
      'configuration' => [
        'hook_implementation_type' => 'oo',
      ],
    ];

    $files = $this->generateModuleFiles($module_data);

    $hooks_file = $files['src/Hooks/TestModuleHooks.php'];

    // $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $hooks_file);
    // $php_tester->assertDrupalCodingStandards();

    dump($hooks_file->getCode());
  }

}
