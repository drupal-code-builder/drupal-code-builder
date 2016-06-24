<?php

/**
 * @file
 * Contains ComponentInfo8Test.
 */

namespace DrupalCodeBuilder\Test;

use Symfony\Component\Yaml\Yaml;

/**
 * Tests for Info component.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/ComponentInfo8Test.php
 * @endcode
 */
class ComponentInfo8Test extends TestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test generating a module info file.
   */
  public function testModuleGenerationInfoFile() {
    $mb_task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    $this->assertTrue(is_object($mb_task_handler_generate), "A task handler object was returned.");

    // Test basic module info properties.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(1, $files, "One file is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .module file.");

    $info_file = $files["$module_name.info.yml"];
    $this->assertNoTrailingWhitespace($info_file);

    $this->assertYamlProperty($info_file, 'name', 'Test Module');
    $this->assertYamlProperty($info_file, 'type', 'module');
    $this->assertYamlProperty($info_file, 'core', '8.x');

    // Test optional info properties.
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'module_package' => 'Test Package',
      'module_dependencies' => ['node', 'block'],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(1, $files, "One file is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .module file.");

    $info_file = $files["$module_name.info.yml"];

    $this->assertYamlProperty($info_file, 'name', 'Test Module');
    $this->assertYamlProperty($info_file, 'type', 'module');
    $this->assertYamlProperty($info_file, 'description', 'Test Module description');
    $this->assertYamlProperty($info_file, 'package', 'Test Package');

    // Array property is too complex for assertYamlProperty().
    $info_array = Yaml::parse($info_file);
    $this->assertArraySubset(['dependencies' => ['node', 'block']], $info_array, "The info file has the correct dependencies.");
  }

}

