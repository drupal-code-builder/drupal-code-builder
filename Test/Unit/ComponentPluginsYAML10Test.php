<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests the PluginYAMLDiscovery generator class.
 *
 * @group yaml
 * @group plugin
 */
class ComponentPluginsYAML10Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 10;

  /**
   * Test PluginYAML component.
   */
  function testBasicYAMLPluginsGeneration() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [],
      'plugins' => [
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
    ];
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
   * Tests plugin derivers.
   */
  function testYamlPluginsGenerationDeriver() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'plugins' => [
        0 => [
          'plugin_type' => 'menu.link',
          'plugin_name' => 'alpha',
          'deriver' => TRUE,
        ]
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.links.menu.yml',
      'src/Plugin/Derivative/AlphaMenuLinkDeriver.php',
    ], $files);

    $deriver = $files['src/Plugin/Derivative/AlphaMenuLinkDeriver.php'];
    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $deriver);
    $php_tester->assertClassHasParent('Drupal\Component\Plugin\Derivative\DeriverBase');
    $php_tester->assertClassHasInterfaces(['Drupal\Core\Plugin\Discovery\ContainerDeriverInterface']);
    $php_tester->assertHasMethod('getDerivativeDefinitions');

    // Check the plugin YAML file declares the deriver.
    $plugin_file = $files["$module_name.links.menu.yml"];

    $yaml_tester = new YamlTester($plugin_file);
    $yaml_tester->assertHasProperty('test_module.alpha');
    $yaml_tester->assertPropertyHasValue(['test_module.alpha', 'deriver'], '\Drupal\test_module\Plugin\Derivative\AlphaMenuLinkDeriver');
  }

  /**
   * Tests a custom plugin class with DI.
   *
   * @group di
   */
  public function testCustomPluginClass(): void {
    // Create a module.
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'plugins' => [
        0 => [
          'plugin_type' => 'menu.link',
          'plugin_name' => 'alpha',
          'plugin_custom_class' => TRUE,
          'injected_services' => [
            'current_user',
          ],
        ]
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.links.menu.yml',
      'src/Plugin/Menu/Link/Alpha.php',
    ], $files);

    $plugins_file = $files['test_module.links.menu.yml'];

    $yaml_tester = new YamlTester($plugins_file);
    $yaml_tester->assertHasProperty('test_module.alpha');
    $yaml_tester->assertPropertyHasValue(['test_module.alpha', 'class'], '\Drupal\test_module\Plugin\Menu\Link\Alpha');

    $plugin_file = $files['src/Plugin/Menu/Link/Alpha.php'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\Menu\Link\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Menu\MenuLinkBase');

    // Check service injection.
    $php_tester->assertClassHasInterfaces([
      'Drupal\Core\Plugin\ContainerFactoryPluginInterface',
    ]);
    $php_tester->assertInjectedServicesWithFactory([
      [
        'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
        'service_name' => 'current_user',
        'property_name' => 'currentUser',
        'parameter_name' => 'current_user',
      ],
    ]);
    $php_tester->assertConstructorBaseParameters([
      'configuration' => 'array',
      'plugin_id' => NULL,
      'plugin_definition' => NULL,
    ]);
  }

  /**
   * Test PluginYAML component with annotated plugins too.
   */
  function testYAMLPluginsGenerationWithAnnotated() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [],
      'plugins' => [
        0 => [
          'plugin_type' => 'menu.link',
          'plugin_name' => 'alpha',
        ],
        1 => [
          'plugin_type' => 'menu.link',
          'plugin_name' => 'beta',
        ],
        2 => [
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
        ],
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "$module_name.links.menu.yml",
      "src/Plugin/Block/Alpha.php",
      "config/schema/test_module.schema.yml",
    ], $files);
  }

  /**
   * Test a menu link plugin with another coming from elsewhere.
   *
   * Tests the requested plugin and the plugin from a config entity type are
   * merged.
   */
  function testPluginsGenerationWithOtherPlugin() {
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [],
      'plugins' => [
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
    ];
    $files = $this->generateModuleFiles($module_data);

    // Check the plugin file.
    $plugin_file = $files["$module_name.links.menu.yml"];

    $yaml_tester = new YamlTester($plugin_file);
    $yaml_tester->assertHasProperty('entity.alpha.collection');
    $yaml_tester->assertHasProperty('test_module.alpha');
  }

}
