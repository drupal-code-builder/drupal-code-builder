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
  ];

  /**
   * Test Tests component.
   */
  function testModuleGenerationTests() {
    // Create a module.
    $module_data = array(
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'tests' => TRUE,
      'requested_build' => array(
        'tests' => TRUE,
        'info' => TRUE,
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'src/Tests/TestModuleTest.php',
    ], $files);

    // Check the .test file.
    $tests_file = $files['src/Tests/TestModuleTest.php'];

    $php_tester = new PHPTester($tests_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('TestModuleTestCase', "The test class file contains the correct class");
    $php_tester->assertHasMethods(['getInfo', 'setUp', 'testTodoChangeThisName']);
  }

}
