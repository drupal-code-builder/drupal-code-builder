<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for Info component.
 *
 * @group yaml
 */
class ComponentInfo8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

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

    $yaml_tester = new YamlTester($info_file);
    $yaml_tester->assertPropertyHasValue('name', 'Test Module');
    $yaml_tester->assertPropertyHasValue('type', 'module');
    $yaml_tester->assertPropertyHasValue('core', '8.x');

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

    $yaml_tester = new YamlTester($info_file);
    $yaml_tester->assertPropertyHasValue('name', 'Test Module');
    $yaml_tester->assertPropertyHasValue('type', 'module');
    $yaml_tester->assertPropertyHasValue('description', 'Test Module description');
    $yaml_tester->assertPropertyHasValue('package', 'Test Package');
    $yaml_tester->assertPropertyHasValue('dependencies', ['node', 'block']);
  }

}
