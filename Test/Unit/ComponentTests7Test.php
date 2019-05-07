<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the Simpletest test class generator.
 */
class ComponentTests7Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 7;

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
    // Drupal 7 does not have the convention of setting a scope on test class
    // methods.
    'Drupal.Scope.MethodScope.Missing',
    // Workaround for https://www.drupal.org/project/coder/issues/2971177, which
    // thinks class methods that have docs saying 'Implements foo' and hooks.
    'Drupal.Commenting.HookComment.HookRepeat',
  ];

  /**
   * Test Tests component.
   */
  function testModuleGenerationTests() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
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

    $this->assertCount(2, $files, "Two files are returned.");

    // Check the .test file.
    $tests_file = $files["tests/$module_name.test"];

    $php_tester = new PHPTester($tests_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('TestModuleTestCase', "The test class file contains the correct class");
    $php_tester->assertHasMethods(['getInfo', 'setUp', 'testTodoChangeThisName']);

    // Check the .info file.
    $info_file = $files["$module_name.info"];

    $this->assertInfoLine($info_file, 'name', $module_data['readable_name'], "The info file declares the module name.");
    $this->assertInfoLine($info_file, 'description', $module_data['short_description'], "The info file declares the module description.");
    $this->assertInfoLine($info_file, 'core', "7.x", "The info file declares the core version.");
    $this->assertInfoLine($info_file, 'files[]', "tests/$module_name.test", "The info file declares the file containing the test class.");
  }

}
