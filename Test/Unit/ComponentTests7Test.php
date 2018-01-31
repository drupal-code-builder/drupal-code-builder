<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the Simpletest test class generator.
 */
class ComponentTests7Test extends TestBaseComponentGeneration {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 7;

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

    $this->assertWellFormedPHP($tests_file);

    $this->assertNoTrailingWhitespace($tests_file, "The tests file contains no trailing whitespace.");

    // Can't use assertWellFormedPHP() as the parent class does not exist here.

    $this->assertFileHeader($tests_file, "The install file contains the correct PHP open tag and file doc header");
    $this->assertClass('TestModuleTestCase', $tests_file, "The test class file contains the correct class");

    // Check the .info file.
    $info_file = $files["$module_name.info"];

    $this->assertInfoLine($info_file, 'name', $module_data['readable_name'], "The info file declares the module name.");
    $this->assertInfoLine($info_file, 'description', $module_data['short_description'], "The info file declares the module description.");
    $this->assertInfoLine($info_file, 'core', "7.x", "The info file declares the core version.");
    $this->assertInfoLine($info_file, 'files[]', "tests/$module_name.test", "The info file declares the file containing the test class.");
  }

}
