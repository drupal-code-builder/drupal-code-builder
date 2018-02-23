<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the Plugin Type generator class.
 */
class ComponentPluginType8Test extends TestBaseComponentGeneration {

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

    $this->assertArrayHasKey('src/CatFeederManager.php', $files, "The plugin manager class file is generated.");
    $this->assertArrayHasKey('src/Annotation/CatFeeder.php', $files, "The annotation class file is generated.");
    $this->assertArrayHasKey('src/Plugin/CatFeeder/CatFeederBase.php', $files, "The plugin base class file is generated.");
    $this->assertArrayHasKey('src/Plugin/CatFeeder/CatFeederInterface.php', $files, "The plugin interface file is generated.");
    $this->assertArrayHasKey('test_module.services.yml', $files, "The services file is generated.");
    $this->assertArrayHasKey('test_module.plugin_type.yml', $files, "The plugin type definition file is generated.");

    // Check the plugin manager file.
    $plugin_manager_file = $files["src/CatFeederManager.php"];
    $this->assertWellFormedPHP($plugin_manager_file);
    $this->assertDrupalCodingStandards($plugin_manager_file);
    $this->parseCode($plugin_manager_file);
    $this->assertHasClass('Drupal\test_module\CatFeederManager');
    $this->assertClassHasParent('Drupal\Core\Plugin\DefaultPluginManager');

    // Check the __construct() method's parameters.
    $this->assertMethodHasParameters([
      'namespaces' => 'Traversable',
      'cache_backend' => 'Drupal\Core\Cache\CacheBackendInterface',
      'module_handler' => 'Drupal\Core\Extension\ModuleHandlerInterface',
    ], '__construct');

    // Check the __construct() method's statements.
    $this->assertStatementIsParentCall('__construct', 0);
    $this->assertCallHasArgs([
      'Plugin/CatFeeder' => 'string',
      'namespaces' => 'var',
      'module_handler' => 'var',
      'Drupal\test_module\Plugin\CatFeeder\CatFeederInterface' => 'class',
      'Drupal\test_module\Annotation\CatFeeder' => 'class',
    ],
    '__construct', 0);

    $this->assertStatementIsLocalMethodCall('alterInfo', '__construct', 1);
    $this->assertCallHasArgs([
      'cat_feeder_info' => 'string',
    ],
    '__construct', 1);

    $this->assertStatementIsLocalMethodCall('setCacheBackend', '__construct', 2);
    $this->assertCallHasArgs([
      'cache_backend' => 'var',
      'cat_feeder_plugins' => 'string',
    ],
    '__construct', 2);

    // Check the annotation class file.
    $annotation_file = $files["src/Annotation/CatFeeder.php"];
    $this->assertWellFormedPHP($annotation_file);
    $this->assertDrupalCodingStandards($annotation_file);
    $this->parseCode($annotation_file);
    $this->assertHasClass('Drupal\test_module\Annotation\CatFeeder');
    $this->assertClassHasParent('Drupal\Component\Annotation\Plugin');
    $this->assertClassHasPublicProperty('id', 'string');
    $this->assertClassHasPublicProperty('label', 'Drupal\Core\Annotation\Translation');
    $this->assertClassDocBlockHasLine('Defines the Cat Feeder plugin annotation object.');
    $this->assertClassDocBlockHasLine('Plugin namespace: CatFeeder.');
    $this->assertClassDocBlockHasLine('@Annotation');

    // Check the plugin base class file.
    $plugin_base_file = $files["src/Plugin/CatFeeder/CatFeederBase.php"];
    $this->assertWellFormedPHP($plugin_base_file);
    $this->assertDrupalCodingStandards($plugin_base_file);
    $this->assertNoTrailingWhitespace($plugin_base_file);

    $this->parseCode($plugin_base_file);
    $this->assertHasClass('Drupal\test_module\Plugin\CatFeeder\CatFeederBase');
    $this->assertClassHasInterfaces(['Drupal\test_module\Plugin\CatFeeder\CatFeederInterface']);

    // Check the plugin interface file.
    $plugin_interface_file = $files["src/Plugin/CatFeeder/CatFeederInterface.php"];
    $this->assertWellFormedPHP($plugin_interface_file);
    $this->assertDrupalCodingStandards($plugin_interface_file);
    $this->assertNoTrailingWhitespace($plugin_interface_file);

    $this->parseCode($plugin_interface_file);
    $this->assertHasInterface('Drupal\test_module\Plugin\CatFeeder\CatFeederInterface');

    // TODO! test further file contents!

    /*
    $file_names = array_keys($files);
    dump($file_names);

    dump($files['src/CatFeederManager.php']);
    dump($files['src/Annotation/CatFeeder.php']);
    dump($files['src/Plugin/CatFeeder/CatFeederBase.php']);
    dump($files['src/Plugin/CatFeeder/CatFeederInterface.php']);
    dump($files['test_module.services.yml']);
    dump($files['test_module.plugin_type.yml']);
    */
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
    $this->assertWellFormedPHP($plugin_manager_file);
    $this->assertDrupalCodingStandards($plugin_manager_file);
    $this->assertNoTrailingWhitespace($plugin_manager_file, "The plugin service class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($plugin_manager_file);

    // Check the files that go in the nested folder.
    // Check the plugin base class file.
    $plugin_base_file = $files["src/Plugin/Animals/CatFeeder/CatFeederBase.php"];
    $this->assertWellFormedPHP($plugin_base_file);
    $this->assertDrupalCodingStandards($plugin_base_file);
    $this->assertNoTrailingWhitespace($plugin_base_file);

    $this->parseCode($plugin_base_file);
    $this->assertHasClass('Drupal\test_module\Plugin\Animals\CatFeeder\CatFeederBase');
    $this->assertClassHasInterfaces(['Drupal\test_module\Plugin\Animals\CatFeeder\CatFeederInterface']);

    // Check the plugin interface file.
    $plugin_interface_file = $files["src/Plugin/Animals/CatFeeder/CatFeederInterface.php"];
    $this->assertWellFormedPHP($plugin_interface_file);
    $this->assertDrupalCodingStandards($plugin_interface_file);
    $this->assertNoTrailingWhitespace($plugin_interface_file);
    $this->parseCode($plugin_interface_file);
    $this->assertHasInterface('Drupal\test_module\Plugin\Animals\CatFeeder\CatFeederInterface');
  }

}
