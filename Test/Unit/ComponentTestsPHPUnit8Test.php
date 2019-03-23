<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests the PHPUnit test class generator.
 */
class ComponentTestsPHPUnit8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * The PHP CodeSniffer rules to exclude for this test class files.
   *
   * @var string[]
   */
  protected $phpcsExcludedSniffs = [
    // This picks up that the setUp() merely calls the parent class, but this
    // is useful to developers as a starting point to add code to, therefore
    // this is excluded.
    'Generic.CodeAnalysis.UselessOverridingMethod',
  ];

  /**
   * Create a test class without a preset.
   */
  function testModuleGenerationTestsWithoutPreset() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'phpunit_tests' => [
        0 => [
          'test_class_name' => 'MyTest',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("tests/src/MyTest.php", $files, "The files list has a test class file.");

    $test_file = $files["tests/src/MyTest.php"];

    $php_tester = new PHPTester($test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\MyTest');
    $php_tester->assertHasMethods(['setUp', 'testMyTest']);
    $php_tester->assertClassHasPublicProperty('modules', 'array', ['system', 'user', 'test_module']);
  }

  /**
   * Create a test class with module dependencies.
   */
  function testModuleGenerationTestsWithModuleDependencies() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'module_dependencies' => [
        'dependency_one',
        'dependency_two',
      ],
      'phpunit_tests' => [
        0 => [
          'test_class_name' => 'MyTest',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("tests/src/MyTest.php", $files, "The files list has a test class file.");

    $test_file = $files["tests/src/MyTest.php"];

    $php_tester = new PHPTester($test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\MyTest');
    $php_tester->assertHasMethods(['setUp', 'testMyTest']);
    $php_tester->assertClassHasPublicProperty('modules', 'array', [
      'system',
      'user',
      'dependency_one',
      'dependency_two',
      'test_module',
    ]);
  }

  /**
   * Create a test class with a preset.
   */
  function testModuleGenerationTestsWithPreset() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Generated module',
      'phpunit_tests' => [
        0 => [
          'test_type' => 'kernel',
          'test_class_name' => 'MyTest',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("tests/src/Kernel/MyTest.php", $files, "The files list has a test class file.");

    $test_file = $files["tests/src/Kernel/MyTest.php"];

    $php_tester = new PHPTester($test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\Kernel\MyTest');
    $php_tester->assertClassHasParent('Drupal\KernelTests\KernelTestBase');
    $php_tester->assertHasMethods(['setUp', 'testMyTest']);
  }

  /**
   * Create a test class with a preset.
   *
   * @group di
   */
  function testModuleGenerationTestsWithServices() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Generated module',
      'phpunit_tests' => [
        0 => [
          'test_type' => 'kernel',
          'test_class_name' => 'MyTest',
          'container_services' => [
            'current_user',
            'entity_type.manager',
          ],
          'mocked_services' => [
            'module_handler',
          ],
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("tests/src/Kernel/MyTest.php", $files, "The files list has a test class file.");

    $test_file = $files["tests/src/Kernel/MyTest.php"];

    $php_tester = new PHPTester($test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\Kernel\MyTest');
    $php_tester->assertClassHasParent('Drupal\KernelTests\KernelTestBase');
    $php_tester->assertHasMethods(['setUp', 'testMyTest']);

    // Container services.
    $php_tester->assertClassHasProtectedProperty('currentUser', 'Drupal\Core\Session\AccountProxyInterface');
    $php_tester->assertClassHasProtectedProperty('entityTypeManager', 'Drupal\Core\Entity\EntityTypeManagerInterface');

    $setup_method_tester = $php_tester->getMethodTester('setUp');
    // Quick and dirty; TODO: better!
    $setup_method_tester->assertHasLine('$this->currentUser = $this->container->get(\'current_user\');');
    $setup_method_tester->assertHasLine('$this->entityTypeManager = $this->container->get(\'entity_type.manager\');');

    $setup_method_tester->assertHasLine('$module_handler = $this->prophesize(ModuleHandlerInterface);');
    $setup_method_tester->assertHasLine('$this->container->set(\'module_handler\', $module_handler->reveal());');
  }

  /**
   * Create a test class with a test module.
   *
   * @group test
   */
  function testModuleGenerationTestsWithTestModule() {
    // Create a module.
    $module_name = 'generated_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Generated module',
      'phpunit_tests' => [
        0 => [
          'test_class_name' => 'MyTest',
          'test_modules' => [
            0 => [
              'root_name' => 'test_module',
              // A plugin inside the test module.
              'plugins' => [
                0 => [
                  'plugin_type' => 'block',
                  'plugin_name' => 'alpha',
                ],
              ],
            ],
          ],
        ],
      ],
      // A plugin outside of the test module, to differentiate.
      'plugins' => [
        0 => [
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);
    $this->assertCount(7, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("generated_module.info.yml", $files, "The main module has a .info.yml file.");
    $this->assertArrayHasKey("src/Plugin/Block/Alpha.php", $files, "The main module has a plugin file.");
    $this->assertArrayHasKey("tests/src/MyTest.php", $files, "The files list has a test class file.");
    $this->assertArrayHasKey("tests/modules/test_module/test_module.info.yml", $files, "The test module has a .info.yml file.");
    $this->assertArrayHasKey("tests/modules/test_module/src/Plugin/Block/Alpha.php", $files, "The test module has a plugin file.");

    // Check the main module .info file.
    $info_file = $files["generated_module.info.yml"];

    $yaml_tester = new YamlTester($info_file);
    $yaml_tester->assertPropertyHasValue('name', 'Generated module');
    $yaml_tester->assertPropertyHasValue('type', 'module');
    $yaml_tester->assertPropertyHasValue('core', '8.x');

    // Check the test module .info file.
    $test_module_info_file = $files['tests/modules/test_module/test_module.info.yml'];

    $yaml_tester = new YamlTester($test_module_info_file);
    $yaml_tester->assertPropertyHasValue('name', 'Test Module');
    $yaml_tester->assertPropertyHasValue('type', 'module');
    $yaml_tester->assertPropertyHasValue('package', 'Testing');
    $yaml_tester->assertPropertyHasValue('core', '8.x');

    // Check the main module plugin file.
    $plugin_file = $files["src/Plugin/Block/Alpha.php"];

    $php_tester = new PHPTester($plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\generated_module\Plugin\Block\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Block\BlockBase');

    // Check the test module plugin file.
    $test_plugin_file = $files["tests/modules/test_module/src/Plugin/Block/Alpha.php"];

    $php_tester = new PHPTester($test_plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Plugin\Block\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Block\BlockBase');

    $test_file = $files["tests/src/MyTest.php"];

    $php_tester = new PHPTester($test_file);
    $php_tester->assertHasClass('Drupal\Tests\generated_module\MyTest');
    $expected_modules_property_value = [
      'system',
      'user',
      'generated_module',
      'test_module',
    ];
    $php_tester->assertClassHasPublicProperty('modules', 'array', $expected_modules_property_value);
  }

}
