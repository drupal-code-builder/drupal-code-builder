<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;
use MutableTypedData\Exception\InvalidInputException;
use PHPUnit\Framework\Assert;
use Psr\Container\ContainerInterface;

/**
 * Tests the Plugins generator class.
 *
 * @group yaml
 * @group plugin
 */
class ComponentPluginsAnnotated8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test Plugins component.
   */
  function testBasicPluginsGeneration() {
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
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
        ]
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(3, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Plugin/Block/Alpha.php", $files, "The files list has a plugin file.");
    $this->assertArrayHasKey("config/schema/test_module.schema.yml", $files, "The files list has a schema file.");

    // Check the plugin file.
    $plugin_file = $files["src/Plugin/Block/Alpha.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\Block\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Block\BlockBase');
    // Interface methods.
    $php_tester->assertHasMethod('blockForm');
    $php_tester->assertHasMethod('blockValidate');
    $php_tester->assertHasMethod('blockForm');

    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('Block');
    $annotation_tester->assertPropertyHasValue('id', 'test_module_alpha');
    $annotation_tester->assertPropertyHasValue('admin_label', 'Alpha');
    $annotation_tester->assertHasProperty('category');

    // Check the config yml file.
    $config_yaml_file = $files["config/schema/test_module.schema.yml"];
    $yaml_tester = new YamlTester($config_yaml_file);
    $yaml_tester->assertHasProperty('block.settings.test_module_alpha');
    $yaml_tester->assertPropertyHasValue(['block.settings.test_module_alpha', 'type'], 'mapping');
    $yaml_tester->assertPropertyHasValue(['block.settings.test_module_alpha', 'label'], 'test_module_alpha');
    $yaml_tester->assertPropertyHasValue(['block.settings.test_module_alpha', 'mapping'], []);
  }

  /**
   * Test plugin with specified class name.
   */
  function testPluginsGenerationClassName() {
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
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
          'plain_class_name' => 'OtherClassName',
        ],
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(3, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("src/Plugin/Block/OtherClassName.php", $files, "The files list has a plugin file, without the derivative prefix in the filename.");

    // Check the plugin file.
    $plugin_file = $files["src/Plugin/Block/OtherClassName.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\Block\OtherClassName');
    $php_tester->assertClassHasParent('Drupal\Core\Block\BlockBase');
  }

  /**
   * Tests special cases where prefixing of the plugin name should be skipped.
   *
   * This also tests that derivative plugin IDs are handled correctly.
   *
   * @dataProvider providerPluginsGenerationNamePrefixing
   */
  public function testPluginsGenerationNamePrefixing(string $plugin_id, string $filename) {
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
          'plugin_type' => 'block',
          'plugin_name' => $plugin_id,
        ],
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "config/schema/test_module.schema.yml",
      "src/Plugin/Block/$filename",
    ], $files);

    $plugin_file = $files["src/Plugin/Block/$filename"];
    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin_file);
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('Block');
    $annotation_tester->assertPropertyHasValue('id', $plugin_id, "The plugin ID has no module prefix.");
  }

  /**
   * Data provder for testPluginsGenerationNamePrefixing().
   */
  public function providerPluginsGenerationNamePrefixing() {
    return [
      'derivative-plugin' => [
        'system_menu_block:alpha',
        'Alpha.php'
      ],
      'module-name-prefix' => [
        'test_module_cake',
        'TestModuleCake.php',
      ],
      'whole-module-name' => [
        'test_module',
        'TestModule.php',
      ],
    ];
  }

  /**
   * Tests an existing plugin class as parent.
   */
  public function testPluginsGenerationParentPluginClass() {
    // Mock the Drupal container and the block plugin manager. Use an anonymous
    // class for the plugin manager as we don't have an interface for it within
    // a test environment.
    $drupal_container = $this->prophesize(ContainerInterface::class);
    $drupal_container
      ->get('plugin.manager.block')
      ->willReturn(new class {
        public function getDefinition($plugin_id) {
          Assert::assertEquals('parent', $plugin_id);

          return [
            // The definition doesn't have the initial '\'.
            'class' => 'Drupal\somemodule\Plugin\Block\ParentBlock',
          ];
        }
      });
    $this->container->get('environment')->setContainer($drupal_container->reveal());

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
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
          'parent_plugin_id' => 'parent',
        ]
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "config/schema/test_module.schema.yml",
      "src/Plugin/Block/Alpha.php",
    ], $files);

    $plugin_file = $files["src/Plugin/Block/Alpha.php"];
    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin_file);
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('Block');
    $annotation_tester->assertPropertyHasValue('id', 'test_module_alpha');
    $annotation_tester->assertPropertyHasValue('admin_label', 'Alpha');

    $php_tester->assertHasClass('Drupal\test_module\Plugin\Block\Alpha');
    $php_tester->assertClassHasParent('Drupal\somemodule\Plugin\Block\ParentBlock');
  }

  /**
   * Tests validation for existing plugin class as parent.
   */
  public function testPluginsGenerationBadParentPluginClass() {
    // Mock the Drupal container and the block plugin manager. Use an anonymous
    // class for the plugin manager as we don't have an interface for it within
    // a test environment.
    $drupal_container = $this->prophesize(ContainerInterface::class);
    $drupal_container
      ->get('plugin.manager.block')
      ->willReturn(new class {
        public function getDefinition($plugin_id) {
          // throw new \Exception("$plugin_id not found.");
          throw new \Drupal\Component\Plugin\Exception\PluginNotFoundException("$plugin_id not found.");
        }
      });
    $this->container->get('environment')->setContainer($drupal_container->reveal());

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
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
          'parent_plugin_id' => 'parent',
        ]
      ],
      'readme' => FALSE,
    ];

    $this->expectException(\DrupalCodeBuilder\Test\Exception\ValidationException::class);
    $this->expectExceptionMessage("module:plugins:0:parent_plugin_id: The 'parent' plugin does not exist.");
    $this->generateModuleFiles($module_data);
  }

  /**
   * Tests replacing a plugin.
   */
  public function testPluginsGenerationReplacePluginClass() {
    // Mock the Drupal container and the block plugin manager. Use an anonymous
    // class for the plugin manager as we don't have an interface for it within
    // a test environment.
    $drupal_container = $this->prophesize(ContainerInterface::class);
    $drupal_container
      ->get('plugin.manager.element_info')
      ->willReturn(new class {
        public function getDefinition($plugin_id) {
          Assert::assertEquals('parent', $plugin_id);

          return [
            // The definition doesn't have the initial '\'.
            'class' => 'Drupal\somemodule\Element\ParentElement',
          ];
        }
      });
    $this->container->get('environment')->setContainer($drupal_container->reveal());

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
          'plugin_type' => 'element_info',
          'plugin_name' => 'alpha',
          'parent_plugin_id' => 'parent',
          'replace_parent_plugin' => TRUE,
        ]
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "test_module.info.yml",
      "test_module.module",
      "src/Element/Alpha.php",
    ], $files);

    $plugin_file = $files["src/Element/Alpha.php"];
    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin_file);

    $php_tester->assertHasClass('Drupal\test_module\Element\Alpha');
    $php_tester->assertClassHasParent('Drupal\somemodule\Element\ParentElement');
    // There should be no annotation.
    $php_tester->assertClassDocBlockNotHasLine('@Element(');

    $module_file = $files['test_module.module'];

    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);
    $php_tester->assertIsProcedural();
    $php_tester->assertHasHookImplementation('hook_element_plugin_alter', $module_name);

    $function_tester = $php_tester->getFunctionTester('test_module_element_plugin_alter');
    $function_tester->assertHasLine("\$info['parent']['class'] = Alpha::class;");
  }

  /**
   * Tests a plugin type where the annotation is just the ID.
   */
  function testPluginWithOnlyId() {
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
          'plugin_type' => 'element_info',
          'plugin_name' => 'alpha',
        ]
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(4, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Element/Alpha.php", $files, "The files list has a plugin file, without the derivative prefix in the filename.");

    $plugin_file = $files["src/Element/Alpha.php"];
    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin_file);
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('RenderElement');
    $annotation_tester->assertAnnotationTextContent('test_module_alpha');
  }

  /**
   * Test Plugins component using the plugin folder name.
   *
   * TODO: Specifying the plugin type with a folder name is temporarily removed
   * as there aren't yet any UIs that use it.
   */
  function XtestPluginsGenerationFromPluginFolder() {
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
          // Specify the folder name rather than the plugin ID.
          'plugin_type' => 'Field/FieldFormatter',
          'plugin_name' => 'alpha',
        ],
        1 => [
          // Specify the namespace slice rather than the plugin ID.
          'plugin_type' => 'Field\FieldFormatter',
          'plugin_name' => 'beta',
        ],
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(4, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Plugin/Field/FieldFormatter/Alpha.php", $files, "The files list has a plugin file.");
    $this->assertArrayHasKey("src/Plugin/Field/FieldFormatter/Beta.php", $files, "The files list has a plugin file.");
  }

  /**
   * Test Plugins component with an invalid plugin type.
   */
  function testPluginsGenerationBadPluginType() {
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
          'plugin_type' => 'made_up',
          'plugin_name' => 'alpha',
        ]
      ],
      'readme' => FALSE,
    ];

    $this->expectException(InvalidInputException::class);

    $files = $this->generateModuleFiles($module_data);
  }

  /**
   * Test Plugins component with injected services.
   *
   * @group di
   */
  function testPluginsGenerationWithServices() {
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
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
          'injected_services' => [
            'current_user',
            'storage:node',
          ],
        ],
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(3, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Plugin/Block/Alpha.php", $files, "The files list has a plugin file.");

    // Check the plugin file.
    $plugin_file = $files["src/Plugin/Block/Alpha.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\Block\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Block\BlockBase');

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
      [
        'typehint' => 'Drupal\Core\Entity\EntityStorageInterface',
        'service_name' => 'node',
        'property_name' => 'nodeStorage',
        'parameter_name' => 'node_storage',
        'extraction_call' => 'getStorage',
      ],
    ]);
    $php_tester->assertConstructorBaseParameters([
      'configuration' => 'array',
      'plugin_id' => NULL,
      'plugin_definition' => NULL,
    ]);
    // Check the construct() method calls the parent.
    // (Not yet covered by PHP Parser assertions.)
    $this->assertFunctionCode($plugin_file, '__construct', 'parent::__construct($configuration, $plugin_id, $plugin_definition);');

    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('Block');
    $annotation_tester->assertPropertyHasValue('id', 'test_module_alpha');
    $annotation_tester->assertHasProperty('admin_label');
    $annotation_tester->assertHasProperty('category');
  }

  /**
   * Test Plugins component with a plugin base class with an existing create().
   *
   * @group di
   */
  function testPluginsGenerationWithExistingCreate() {
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
          'plugin_type' => 'image.effect',
          'plugin_name' => 'alpha',
          'injected_services' => [
            'current_user',
            'storage:node',
          ],
        ],
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(3, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Plugin/ImageEffect/Alpha.php", $files, "The files list has a plugin file.");

    // Check the plugin file.
    $plugin_file = $files["src/Plugin/ImageEffect/Alpha.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\ImageEffect\Alpha');
    $php_tester->assertClassHasParent('Drupal\image\ImageEffectBase');

    // Check service injection.
    $php_tester->assertClassHasNotInterfaces([
      'Drupal\Core\Plugin\ContainerFactoryPluginInterface',
    ], "The plugin doesn't implement ContainerFactoryPluginInterface, because the parent class already does.");
    $php_tester->assertInjectedServicesWithFactory([
      [
        'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
        'service_name' => 'current_user',
        'property_name' => 'currentUser',
        'parameter_name' => 'current_user',
      ],
      [
        'typehint' => 'Drupal\Core\Entity\EntityStorageInterface',
        'service_name' => 'node',
        'property_name' => 'nodeStorage',
        'parameter_name' => 'node_storage',
        'extraction_call' => 'getStorage',
      ],
    ]);
    $php_tester->assertConstructorBaseParameters([
      'configuration' => 'array',
      'plugin_id' => NULL,
      'plugin_definition' => NULL,
      'logger' => 'Psr\Log\LoggerInterface',
      /*
      // TODO: figure out how to assert this.
      [
        'typehint' => 'Psr\Log\LoggerInterface',
        'service_name' => 'logger.factory',
        'property_name' => 'logger',
        'parameter_name' => 'logger',
      ],
      */
    ]);
    // Check the construct() method calls the parent.
    // (Not yet covered by PHP Parser assertions.)
    $this->assertFunctionCode($plugin_file, '__construct', 'parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);');
    // Check the create() method's return statement has an argument for the
    // base class's service.
    // (Not yet covered by PHP Parser assertions.)
    $this->assertFunctionCode($plugin_file, 'create', '$container->get(\'logger.factory\')->get(\'image\'),');
  }

  /**
   * Tests a plugin base class with nonstandard fixed constructor parameters.
   *
   * @group di
   */
  function testPluginsGenerationWithNonstandardFixedParameters() {
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
          'plugin_type' => 'field.formatter',
          'plugin_name' => 'alpha',
          'injected_services' => [
            'current_user',
            'storage:node',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    // Check the plugin file.
    $plugin_file = $files["src/Plugin/Field/FieldFormatter/Alpha.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\Field\FieldFormatter\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Field\FormatterBase');

    // Check service injection.
    $php_tester->assertClassHasNotInterfaces([
      'Drupal\Core\Plugin\ContainerFactoryPluginInterface',
    ], "The DI interface is not used by the class because the base class already implements it.");

    $create_tester = $php_tester->getMethodTester('create');
    $create_tester->assertHasParameters([
      'container' => 'Symfony\Component\DependencyInjection\ContainerInterface',
      'configuration' => 'array',
      'plugin_id' => NULL,
      'plugin_definition' => NULL,
    ]);

    $php_tester->assertConstructorBaseParameters([
      'plugin_id' => NULL,
      'plugin_definition' => NULL,
      'field_definition' => 'Drupal\Core\Field\FieldDefinitionInterface',
      'settings' => 'array',
      'label' => NULL,
      'view_mode' => NULL,
      'third_party_settings' => 'array',
    ]);

    $php_tester->assertInjectedServicesWithFactory([
      [
        'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
        'service_name' => 'current_user',
        'property_name' => 'currentUser',
        'parameter_name' => 'current_user',
      ],
      [
        'typehint' => 'Drupal\Core\Entity\EntityStorageInterface',
        'service_name' => 'node',
        'property_name' => 'nodeStorage',
        'parameter_name' => 'node_storage',
        'extraction_call' => 'getStorage',
      ],
    ]);
  }

  /**
   * Test Plugins component with schema for other components.
   *
   * Tests schema for the plugin and for a config entity type are both merged
   * in the config YAML file.
   */
  function testPluginsGenerationWithOtherSchema() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'plugins' => [
        0 => [
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
        ],
      ],
      'config_entity_types' => [
        0 => [
          'entity_type_id' => 'cake',
          'entity_properties' => [
            0 => [
              'name' => 'filling',
              'type' => 'text',
            ],
            1 => [
              'name' => 'colour',
              'type' => 'text',
            ],
          ],
        ],
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(5, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Plugin/Block/Alpha.php", $files, "The files list has a plugin file.");
    $this->assertArrayHasKey("src/Entity/Cake.php", $files);
    $this->assertArrayHasKey("src/Entity/CakeInterface.php", $files);

    // Check the config yml file.
    $config_yaml_file = $files["config/schema/test_module.schema.yml"];

    $yaml_tester = new YamlTester($config_yaml_file);
    $yaml_tester->assertHasProperty('block.settings.test_module_alpha');
    $yaml_tester->assertHasProperty('test_module.cake.*');
    $yaml_tester->assertPropertyHasBlankLineBefore(['block.settings.test_module_alpha']);
    // TODO: assert deeper into the YAML.
  }

  /**
   * Test validation constraint plugin generation.
   */
  function testValidationConstraint() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'plugins' => [
        0 => [
          'plugin_type' => 'validation.constraint',
          'plugin_name' => 'alpha',
        ],
      ],
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "src/Plugin/Validation/Constraint/Alpha.php",
      "src/Plugin/Validation/Constraint/AlphaValidator.php",
    ], $files);

    $plugin = $files['src/Plugin/Validation/Constraint/Alpha.php'];
    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\Validation\Constraint\Alpha');
    $php_tester->assertClassHasParent('Symfony\Component\Validator\Constraint');

    $validator = $files["src/Plugin/Validation/Constraint/AlphaValidator.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $validator);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\Validation\Constraint\AlphaValidator');
    $php_tester->assertClassHasParent('Symfony\Component\Validator\ConstraintValidator');
    $php_tester->assertHasMethod('validate');
  }

  /**
   * Test render element plugin generation.
   */
  function testRenderElement() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'plugins' => [
        0 => [
          'plugin_type' => 'element_info',
          'plugin_name' => 'alpha',
        ],
      ],
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "src/Element/Alpha.php",
      "templates/alpha.html.twig",
      "test_module.module",
    ], $files);

    $plugin = $files['src/Element/Alpha.php'];
    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Element\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Render\Element\RenderElement');
    $php_tester->assertHasMethod('getInfo');

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);
    $php_tester->assertDrupalCodingStandards();

    $php_tester->assertHasHookImplementation('hook_theme', $module_name);

    // Check that the hook_theme() implementation has the generated code.
    // This covers the specialized HookTheme hook generator class getting used.
    $this->assertFunctionCode($module_file, "{$module_name}_theme", "'alpha' =>");
    $this->assertFunctionCode($module_file, "{$module_name}_theme", "'render element' => 'elements',");

    $template_file = $files['templates/alpha.html.twig'];
    $this->assertStringContainsString("Default theme implementation to display a alpha.", $template_file);
  }

}

namespace Drupal\Component\Plugin\Exception;
if (!class_exists(\Drupal\Component\Plugin\Exception\PluginNotFoundException::class)) {
  /**
   * Define the exception for testPluginsGenerationBadParentPluginClass().
   */
  class PluginNotFoundException extends \Exception {}
}
