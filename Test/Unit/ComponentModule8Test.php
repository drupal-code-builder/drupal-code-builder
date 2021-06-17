<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use MutableTypedData\Data\DataItem;

/**
 * Tests basic module generation.
 *
 * @group hooks
 */
class ComponentModule8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Tests a UI can access all of the necessary methods on component data.
   */
  public function testUiAccess() {
    $component_data = $this->getRootComponentBlankData('module');

    $this->simulateUiWalk($component_data);

    $this->assertTrue(TRUE, 'We made it without crashing!');
  }

  /**
   * Helper for testUi().
   *
   * Recursively accesses label, descriptions, options on data items, creating
   * them as it goes to cover the whole data structure.
   *
   * TODO: doesn't handle mutable!
   */
  protected function simulateUiWalk(DataItem $data_item) {
    // Get the label and description.
    // If these are not properly defined, MTD will throw exceptions.
    $data_item->getLabel();
    $data_item->getDescription();

    if ($data_item->hasOptions()) {
      $options = $data_item->getOptions();
      foreach ($options as $value => $option) {
        // Get the label and description for the option.
        // If these are not properly defined, MTD will throw exceptions.
        $option->getLabel();
        $option->getDescription();
      }
    }

    // Recurse.
    foreach ($data_item as $property => $property_data_item) {
      // Ensure that data is created for complex properties and a single delta.
      $property_data_item->access();

      if ($property_data_item->isMultiple()) {
        $property_data_item->createItem();
      }

      $this->simulateUiWalk($property_data_item);
    }
  }

  /**
   * Tests getting module configuration data.
   *
   * @group config
   */
  public function testConfiguration() {
    $config_data = \DrupalCodeBuilder\Factory::getTask('Configuration')->getConfigurationData('module');
    $properties = $config_data->getProperties();

    $this->assertArrayHasKey('service_namespace', $properties);
    $this->assertArrayHasKey('entity_handler_namespace', $properties);
  }

  /**
   * Tests the token replacements for modules.
   */
  public function testModule8TokenReplacements() {
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'readme' => FALSE,
    ];

    $component_data = $this->getRootComponentBlankData('module');
    $component_data->set($module_data);

    $component_collector = \DrupalCodeBuilder\Factory::getContainer()->get('Generate\ComponentCollector');
    $component_collection = $component_collector->assembleComponentList($component_data);

    $module_component = $component_collection->getRootComponent();
    $variables = $module_component->getReplacements();

    $this->assertEquals('test_module', $variables['%module']);
    $this->assertEquals('Test Module', $variables['%readable']);
    $this->assertEquals('Test Module', $variables['%Module']);
    $this->assertEquals('Test module', $variables['%sentence']);
    $this->assertEquals('test module', $variables['%lower']);
  }

  /**
   * Test requesting a module with no options produces basic files.
   */
  function testNoOptions() {
    // Create a module.
    $module_name = 'testmodule8a';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(1, $files, "One file is returned.");

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
  }

  /**
   * Test the helptext option produces hook_help().
   */
  function testHelptextOption() {
    // Create a module.
    $module_name = 'testmodule8b';
    $help_text = 'This is the test help text';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'readme' => FALSE,
      'module_help_text' => $help_text,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Two files are returned.");

    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);
    $php_tester->assertDrupalCodingStandards();

    $php_tester->assertHasHookImplementation('hook_help', $module_name);

    $this->assertFunctionCode($module_file, $module_name . '_help', $help_text, "The hook_help() implementation contains the requested help text.");
  }

}
