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
   * Create a test class with module dependencies.
   */
  function testModuleGenerationTestsWithModuleDependencies() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'module_dependencies' => [
        'dependency_one',
        'dependency_two',
      ],
      'phpunit_tests' => [
        0 => [
          'test_type' => 'kernel',
          'plain_class_name' => 'MyTest',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "test_module.info.yml",
      "tests/src/Kernel/MyTest.php"
    ], $files);

    $this->assertArrayHasKey("tests/src/Kernel/MyTest.php", $files, "The files list has a test class file.");

    $test_file = $files["tests/src/Kernel/MyTest.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\Kernel\MyTest');
    $php_tester->assertHasMethods(['setUp', 'testMyTest']);
    $php_tester->assertClassHasProtectedProperty('modules', 'array', [
      'system',
      'user',
      'dependency_one',
      'dependency_two',
      'test_module',
    ]);
  }

  /**
   * Create a basic unit test.
   */
  function testModuleGenerationUnitTest() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Generated module',
      'phpunit_tests' => [
        0 => [
          'test_type' => 'unit',
          'plain_class_name' => 'MyTest',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "test_module.info.yml",
      "tests/src/Unit/MyTest.php"
    ], $files);

    $test_file = $files["tests/src/Unit/MyTest.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\Unit\MyTest');
    $php_tester->assertClassHasParent('Drupal\Tests\UnitTestCase');
    $php_tester->assertClassHasNotProperty('modules');
    $php_tester->assertHasMethods(['setUp', 'testMyTest']);
    $setup_method_tester = $php_tester->getMethodTester('setUp');
    $setup_method_tester->assertReturnType('void');
  }

  /**
   * Create a basic kernel test.
   */
  function testModuleGenerationKernelTest() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Generated module',
      'phpunit_tests' => [
        0 => [
          'test_type' => 'kernel',
          'plain_class_name' => 'MyTest',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "test_module.info.yml",
      "tests/src/Kernel/MyTest.php"
    ], $files);

    $test_file = $files["tests/src/Kernel/MyTest.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\Kernel\MyTest');
    $php_tester->assertClassHasParent('Drupal\KernelTests\KernelTestBase');
    $php_tester->assertClassHasProtectedProperty('modules', 'array', [
      'system',
      'user',
      'test_module',
    ]);
    $php_tester->assertHasMethods(['setUp', 'testMyTest']);
    $setup_method_tester = $php_tester->getMethodTester('setUp');
    $setup_method_tester->assertReturnType('void');
  }

  /**
   * Create a test class with services.
   *
   * @group di
   */
  function testModuleGenerationTestsWithServices() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Generated module',
      'phpunit_tests' => [
        0 => [
          'test_type' => 'kernel',
          'plain_class_name' => 'MyTest',
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
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "test_module.info.yml",
      "tests/src/Kernel/MyTest.php"
    ], $files);

    $test_file = $files["tests/src/Kernel/MyTest.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $test_file);
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

    $setup_method_tester->assertHasLine('$module_handler = $this->prophesize(ModuleHandlerInterface::class);');
    $setup_method_tester->assertHasLine('$this->container->set(\'module_handler\', $module_handler->reveal());');
  }

  /**
   * Create a test class with a test module.
   *
   * @group test
   */
  function testModuleGenerationTestsWithBasicTestModule() {
    // Create a module.
    $module_name = 'generated_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Generated module',
      'phpunit_tests' => [
        0 => [
          'test_type' => 'kernel',
          'plain_class_name' => 'MyTest',
          'test_modules' => [
            0 => [
              'root_name' => 'test_module',
            ],
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'generated_module.info.yml',
      'tests/src/Kernel/MyTest.php',
      'tests/modules/test_module/test_module.info.yml',
    ], $files);
  }

  /**
   * Create a test class with a test module and module components.
   *
   * @group test
   */
  function testModuleGenerationTestsWithTestModuleComponents() {
    // Create a module.
    $module_name = 'generated_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Generated module',
      'phpunit_tests' => [
        0 => [
          'test_type' => 'kernel',
          'plain_class_name' => 'MyTest',
          'test_modules' => [
            0 => [
              // Don't specify root_name so the default is applied.
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
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'generated_module.info.yml',
      'src/Plugin/Block/Alpha.php',
      'config/schema/generated_module.schema.yml',
      'tests/src/Kernel/MyTest.php',
      'tests/modules/my_test/my_test.info.yml',
      'tests/modules/my_test/src/Plugin/Block/Alpha.php',
      'tests/modules/my_test/config/schema/my_test.schema.yml',
    ], $files);

    // Check the main module .info file.
    $info_file = $files["generated_module.info.yml"];

    $yaml_tester = new YamlTester($info_file);
    $yaml_tester->assertPropertyHasValue('name', 'Generated module');
    $yaml_tester->assertPropertyHasValue('type', 'module');
    $yaml_tester->assertPropertyHasValue('core', '8.x');

    // Check the test module .info file.
    $test_module_info_file = $files['tests/modules/my_test/my_test.info.yml'];

    $yaml_tester = new YamlTester($test_module_info_file);
    $yaml_tester->assertPropertyHasValue('name', 'My Test');
    $yaml_tester->assertPropertyHasValue('type', 'module');
    $yaml_tester->assertPropertyHasValue('package', 'Testing');
    $yaml_tester->assertPropertyHasValue('core', '8.x');

    // Check the main module plugin file.
    $plugin_file = $files["src/Plugin/Block/Alpha.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\generated_module\Plugin\Block\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Block\BlockBase');

    // Check the test module plugin file.
    $test_plugin_file = $files["tests/modules/my_test/src/Plugin/Block/Alpha.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $test_plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\my_test\Plugin\Block\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Block\BlockBase');

    $test_file = $files["tests/src/Kernel/MyTest.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $test_file);
    $php_tester->assertHasClass('Drupal\Tests\generated_module\Kernel\MyTest');
    $expected_modules_property_value = [
      'system',
      'user',
      'generated_module',
      'my_test',
    ];
    $php_tester->assertClassHasProtectedProperty('modules', 'array', $expected_modules_property_value);
  }

}
