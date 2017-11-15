<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the PHPUnit test class generator.
 */
class ComponentTestsPHPUnitTest8 extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * The PHP CodeSniffer to exclude for this test.
   *
   * @var string[]
   */
  static protected $phpcsExcludedSniffs = [
    // This picks up that the setUp() merely calls the parent class, but this
    // is useful to developers as a starting point to add code to, therefore
    // this is excluded.
    'Generic.CodeAnalysis.UselessOverridingMethod',
  ];

  /**
   * Create a test class without a preset.
   */
  function testModuleGenerationTestsWithoutPreset() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'phpunit_tests' => [
        0 => [
          'test_class_name' => 'MyTest',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("tests/src/MyTest.php", $files, "The files list has a test class file.");

    $test_file = $files["tests/src/MyTest.php"];

    $this->assertWellFormedPHP($test_file);
    $this->assertDrupalCodingStandards($test_file);
    $this->assertNoTrailingWhitespace($test_file, "The test class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($test_file);

    $this->assertNamespace(['Drupal', 'Tests', $module_name], $test_file, "The test class file contains contains the expected namespace.");
    $this->assertClass('MyTest', $test_file, "The test file contains the form class.");
  }

  /**
   * Create a test class with a preset.
   */
  function testModuleGenerationTestsWithPreset() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'phpunit_tests' => [
        0 => [
          'test_type' => 'kernel',
          'test_class_name' => 'MyTest',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("tests/src/Kernel/MyTest.php", $files, "The files list has a test class file.");

    $test_file = $files["tests/src/Kernel/MyTest.php"];

    $this->assertWellFormedPHP($test_file);
    $this->assertDrupalCodingStandards($test_file);
    $this->assertNoTrailingWhitespace($test_file, "The test class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($test_file);

    $this->assertNamespace(['Drupal', 'Tests', $module_name, 'Kernel'], $test_file, "The test class file contains contains the expected namespace.");
    $this->assertClass('MyTest extends KernelTestBase', $test_file, "The test file contains the form class.");
  }

}
