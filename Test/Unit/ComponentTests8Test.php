<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the Simpletest test class generator.
 */
class ComponentTests8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * The PHP CodeSniffer rules to exclude for this test class files.
   *
   * @var string[]
   */
  protected $phpcsExcludedSniffs = [
    // This picks up that the setUp() merely calls the parent class, but this
    // is useful to developers as a starting point to add code to, therefore
    // this is excluded.
    'Generic.CodeAnalysis.UselessOverridingMethod',
    // Ignore this error in the test class. Simpletest tests are deprecated in
    // Drupal 8 anyway.
    'Drupal.Scope.MethodScope.Missing',
    // Workaround for https://www.drupal.org/project/coder/issues/2971177, which
    // thinks class methods that have docs saying 'Implements foo' and hooks.
    // The generated methods should really say '@inheritdoc', but simpletest
    // tests are deprecated anyway, so who cares.
    'Drupal.Commenting.HookComment.HookRepeat',
    // This is disabled because I CBA to fix the Simpletest tests as they're
    // obsolete anyway.
    'Drupal.Arrays.DisallowLongArraySyntax.Found',
  ];

  /**
   * Test Tests component.
   */
  function testModuleGenerationTests() {
    // Create a module.
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'tests' => TRUE,
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'src/Tests/TestModuleTestCase.php',
    ], $files);

    // Check the .test file.
    $tests_file = $files['src/Tests/TestModuleTestCase.php'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $tests_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('TestModuleTestCase', "The test class file contains the correct class");
    $php_tester->assertHasMethods(['getInfo', 'setUp', 'testTodoChangeThisName']);
  }

}
