<?php

namespace DrupalCodeBuilder\Test\Unit;

use \DrupalCodeBuilder\Exception\InvalidInputException;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests the PluginYAML generator class.
 *
 * @group yaml
 */
class ComponentPluginsYAML8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test PluginYAML component.
   */
  function testBasicYAMLPluginsGeneration() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [],
      'plugins_yaml' => [
        0 => [
          'plugin_type' => 'menu.link',
          'plugin_name' => 'alpha',
        ],
        1 => [
          'plugin_type' => 'menu.link',
          'plugin_name' => 'beta',
        ],
      ],
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("$module_name.links.menu.yml", $files, "The files list has a YAML plugin file.");

    // Check the plugin file.
    $plugin_file = $files["$module_name.links.menu.yml"];

    $yaml_tester = new YamlTester($plugin_file);
    $yaml_tester->assertHasProperty('test_module.alpha');
  }

  /**
   * Test PluginYAML component with annotated plugins too.
   */
  function testYAMLPluginsGenerationWithAnnotated() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [],
      'plugins_yaml' => [
        0 => [
          'plugin_type' => 'menu.link',
          'plugin_name' => 'alpha',
        ],
        1 => [
          'plugin_type' => 'menu.link',
          'plugin_name' => 'beta',
        ],
      ],
      'plugins' => [
        0 => [
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
        ],
      ],
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(4, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("$module_name.links.menu.yml", $files, "The files list has a YAML plugin file.");
    $this->assertArrayHasKey("src/Plugin/Block/Alpha.php", $files, "The files list has a plugin file.");
    $this->assertArrayHasKey("config/schema/test_module.schema.yml", $files, "The files list has a schema file.");
  }

  /**
   * Test a menu link plugin with another coming from elsewhere.
   *
   * Tests the requested plugin and the plugin from a config entity type are
   * merged.
   */
  function testPluginsGenerationWithOtherPlugin() {
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [],
      'plugins_yaml' => [
        0 => [
          'plugin_type' => 'menu.link',
          'plugin_name' => 'alpha',
        ],
      ],
      'config_entity_types' => [
        0 => [
          'entity_type_id' => 'alpha',
          // Request en entity UI so menu plugins are generated.
          'entity_ui' => 'admin',
        ],
      ],
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    // Check the plugin file.
    $plugin_file = $files["$module_name.links.menu.yml"];

    $yaml_tester = new YamlTester($plugin_file);
    $yaml_tester->assertHasProperty('entity.alpha.collection');
    $yaml_tester->assertHasProperty('test_module.alpha');
  }

}
