<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests basic module generation.
 *
 * @group hooks
 */
class ComponentModule7Test extends TestBaseComponentGeneration {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(7);
  }

  /**
   * Tests preparation of component properties.
   *
   * Tests in general don't use the prepareComponentDataProperty(), and just
   * run generation with full values. This test checks that callbacks in the
   * property info don't rely on things they don't have at this stage.
   *
   * @group prepare
   */
  public function testModule7PropertyPreparation() {
    $this->task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    $component_data_info = $this->task_handler_generate->getRootComponentDataInfo();

    $values = [];

    foreach ($component_data_info as $property_name => &$property_info) {
      $this->task_handler_generate->prepareComponentDataProperty($property_name, $property_info, $values);
    }

    // Check that options got expanded.
    $this->assertInternalType('array', $component_data_info['hooks']['options'], "The module hooks options are expanded.");
  }

  /**
   * Test requesting a module with no options produces basic files.
   */
  function testNoOptions() {
    // Create a module.
    $module_name = 'testmodule';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Two files are returned.");

    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.info", $files, "The files list has a .info file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $this->assertWellFormedPHP($module_file);
  }

  /**
   * Test the helptext option produces hook_help().
   */
  function testHelptextOption() {
    // Create a module.
    $module_name = 'testmodule';
    $help_text = 'This is the test help text';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'readme' => FALSE,
      'module_help_text' => $help_text,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Two files are returned.");

    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.info", $files, "The files list has a .info file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $php_tester = new PHPTester($module_file);

    $php_tester->assertDrupalCodingStandards();

    $php_tester->assertHasHookImplementation('hook_help', $module_name);

    $this->assertFunctionCode($module_file, $module_name . '_help', $help_text, "The hook_help() implementation contains the requested help text.");
  }

}
