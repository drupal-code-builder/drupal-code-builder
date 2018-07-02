<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests the Plugin Type generator class.
 *
 * @group yaml
 */
class ComponentPluginType8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test Plugin Type component.
   */
  function testBasicPluginTypeGeneration() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'plugin_types' => array(
        0 => [
          'plugin_type' => 'cat_feeder',
        ]
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'src/CatFeederManager.php',
      'src/Annotation/CatFeeder.php',
      'src/Plugin/CatFeeder/CatFeederBase.php',
      'src/Plugin/CatFeeder/CatFeederInterface.php',
      'test_module.services.yml',
      'test_module.plugin_type.yml',
      'test_module.api.php',
    ], $files);

    $this->assertArrayHasKey('src/CatFeederManager.php', $files, "The plugin manager class file is generated.");
    $this->assertArrayHasKey('src/Annotation/CatFeeder.php', $files, "The annotation class file is generated.");
    $this->assertArrayHasKey('src/Plugin/CatFeeder/CatFeederBase.php', $files, "The plugin base class file is generated.");
    $this->assertArrayHasKey('src/Plugin/CatFeeder/CatFeederInterface.php', $files, "The plugin interface file is generated.");
    $this->assertArrayHasKey('test_module.services.yml', $files, "The services file is generated.");
    $this->assertArrayHasKey('test_module.plugin_type.yml', $files, "The plugin type definition file is generated.");
    $this->assertArrayHasKey('test_module.api.php', $files, "The files list has an api.php file.");

    // Check the plugin manager file.
    $plugin_manager_file = $files["src/CatFeederManager.php"];

    $php_tester = new PHPTester($plugin_manager_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\CatFeederManager');
    $php_tester->assertClassHasParent('Drupal\Core\Plugin\DefaultPluginManager');

    $constructor_tester = $php_tester->getMethodTester('__construct');
    // Check the __construct() method's parameters.
    $constructor_tester->assertHasParameters([
      'namespaces' => 'Traversable',
      'cache_backend' => 'Drupal\Core\Cache\CacheBackendInterface',
      'module_handler' => 'Drupal\Core\Extension\ModuleHandlerInterface',
    ]);

    // Check the __construct() method's statements.
    $php_tester->assertStatementIsParentCall('__construct', 0);
    $php_tester->assertCallHasArgs([
      'Plugin/CatFeeder' => 'string',
      'namespaces' => 'var',
      'module_handler' => 'var',
      'Drupal\test_module\Plugin\CatFeeder\CatFeederInterface' => 'class',
      'Drupal\test_module\Annotation\CatFeeder' => 'class',
    ],
    '__construct', 0);

    $php_tester->assertStatementIsLocalMethodCall('alterInfo', '__construct', 1);
    $php_tester->assertCallHasArgs([
      'cat_feeder_info' => 'string',
    ],
    '__construct', 1);

    $php_tester->assertStatementIsLocalMethodCall('setCacheBackend', '__construct', 2);
    $php_tester->assertCallHasArgs([
      'cache_backend' => 'var',
      'cat_feeder_plugins' => 'string',
    ],
    '__construct', 2);

    // Check the annotation class file.
    $annotation_file = $files["src/Annotation/CatFeeder.php"];

    $php_tester = new PHPTester($annotation_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Annotation\CatFeeder');
    $php_tester->assertClassHasParent('Drupal\Component\Annotation\Plugin');
    $php_tester->assertClassHasPublicProperty('id', 'string');
    $php_tester->assertClassHasPublicProperty('label', 'Drupal\Core\Annotation\Translation');
    $php_tester->assertClassDocBlockHasLine('Defines the Cat Feeder plugin annotation object.');
    $php_tester->assertClassDocBlockHasLine('Plugin namespace: CatFeeder.');
    $php_tester->assertClassDocBlockHasLine('@Annotation');

    // Check the plugin base class file.
    $plugin_base_file = $files["src/Plugin/CatFeeder/CatFeederBase.php"];

    $php_tester = new PHPTester($plugin_base_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\CatFeeder\CatFeederBase');
    $php_tester->assertClassHasInterfaces(['Drupal\test_module\Plugin\CatFeeder\CatFeederInterface']);

    // Check the plugin interface file.
    $plugin_interface_file = $files["src/Plugin/CatFeeder/CatFeederInterface.php"];

    $php_tester = new PHPTester($plugin_interface_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasInterface('Drupal\test_module\Plugin\CatFeeder\CatFeederInterface');

    // Check the services file.
    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "plugin.manager.test_module_cat_feeder"]);
    $yaml_tester->assertPropertyHasValue(['services', "plugin.manager.test_module_cat_feeder", 'class'], 'Drupal\test_module\CatFeederManager');
    $yaml_tester->assertPropertyHasValue(['services', "plugin.manager.test_module_cat_feeder", 'parent'], "default_plugin_manager");

    // Check the api.php file.
    $api_file = $files["$module_name.api.php"];

    $php_tester = new PHPTester($api_file);
    $php_tester->assertDrupalCodingStandards();

    // TODO: expand the docblock assertion for these.
    $this->assertContains("Hooks provided by the Test module module.", $api_file, 'The API file contains the correct docblock header.');
    $this->assertContains("@addtogroup hooks", $api_file, 'The API file contains the addtogroup docblock tag.');
    $this->assertContains('@} End of "addtogroup hooks".', $api_file, 'The API file contains the closing addtogroup docblock tag.');

    $php_tester->assertHasFunction('hook_cat_feeder_info_alter');

    // Check the plugin type file.
    $plugin_type_file = $files['test_module.plugin_type.yml'];

    $yaml_tester = new YamlTester($plugin_type_file);
    $yaml_tester->assertHasProperty('test_module.cat_feeder');
    $yaml_tester->assertPropertyHasValue(['test_module.cat_feeder', 'label'], 'Cat Feeder');
    $yaml_tester->assertPropertyHasValue(['test_module.cat_feeder', 'provider'], 'test_module');
    $yaml_tester->assertPropertyHasValue(['test_module.cat_feeder', 'plugin_manager_service_id'], 'plugin.manager.test_module_cat_feeder');
    $yaml_tester->assertPropertyHasValue(['test_module.cat_feeder', 'plugin_definition_decorator_class'], 'Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator');
  }

  /**
   * Test Plugin Type component with a nested plugin folder.
   */
  function testPluginTypeGenerationWithNestedFolder() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'plugin_types' => array(
        0 => [
          'plugin_type' => 'cat_feeder',
          'plugin_subdirectory' => 'Animals/CatFeeder'
        ]
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    // Check the plugin manager file, as it mentions the interface.
    $plugin_manager_file = $files["src/CatFeederManager.php"];

    $php_tester = new PHPTester($plugin_manager_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\CatFeederManager');

    // Check the files that go in the nested folder.
    // Check the plugin base class file.
    $plugin_base_file = $files["src/Plugin/Animals/CatFeeder/CatFeederBase.php"];

    $php_tester = new PHPTester($plugin_base_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\Animals\CatFeeder\CatFeederBase');
    $php_tester->assertClassHasInterfaces(['Drupal\test_module\Plugin\Animals\CatFeeder\CatFeederInterface']);

    // Check the plugin interface file.
    $plugin_interface_file = $files["src/Plugin/Animals/CatFeeder/CatFeederInterface.php"];

    $php_tester = new PHPTester($plugin_interface_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasInterface('Drupal\test_module\Plugin\Animals\CatFeeder\CatFeederInterface');
  }

}
