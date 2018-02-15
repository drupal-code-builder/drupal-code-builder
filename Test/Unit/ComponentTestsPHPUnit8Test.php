<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the PHPUnit test class generator.
 */
class ComponentTestsPHPUnit8Test extends TestBaseComponentGeneration {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * The PHP CodeSniffer to exclude for this test.
   *
   * @var string[]
   */
  static protected $phpcsExcludedSniffs = [
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

    $this->assertWellFormedPHP($test_file);
    $this->assertDrupalCodingStandards($test_file);
    $this->assertNoTrailingWhitespace($test_file, "The test class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($test_file);

    $this->parseCode($test_file);
    $this->assertHasClass('Drupal\Tests\test_module\MyTest');
    $this->assertHasMethods(['setUp', 'testMyTest']);
    $this->assertClassHasPublicProperty('modules', 'array', ['system', 'user', 'test_module']);
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

    $this->assertWellFormedPHP($test_file);
    $this->assertDrupalCodingStandards($test_file);
    $this->assertNoTrailingWhitespace($test_file, "The test class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($test_file);

    $this->parseCode($test_file);
    $this->assertHasClass('Drupal\Tests\test_module\MyTest');
    $this->assertHasMethods(['setUp', 'testMyTest']);
    $this->assertClassHasPublicProperty('modules', 'array', [
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

    $this->assertWellFormedPHP($test_file);
    $this->assertDrupalCodingStandards($test_file);
    $this->assertNoTrailingWhitespace($test_file, "The test class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($test_file);

    $this->parseCode($test_file);
    $this->assertHasClass('Drupal\Tests\test_module\Kernel\MyTest');
    $this->assertClassHasParent('Drupal\KernelTests\KernelTestBase');
    $this->assertHasMethods(['setUp', 'testMyTest']);
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
    $this->assertYamlProperty($info_file, 'name', "Generated module", "The info file declares the module name.");
    $this->assertYamlProperty($info_file, 'core', "8.x", "The info file declares the core version.");

    // Check the test module .info file.
    $test_module_info_file = $files['tests/modules/test_module/test_module.info.yml'];
    $this->assertYamlProperty($test_module_info_file, 'name', "Test Module", "The info file declares the module name.");
    $this->assertYamlProperty($test_module_info_file, 'package', "Testing", "The info file declares the package as 'Testing'.");
    $this->assertYamlProperty($test_module_info_file, 'core', "8.x", "The info file declares the core version.");

    // Check the main module plugin file.
    $plugin_file = $files["src/Plugin/Block/Alpha.php"];
    $this->assertWellFormedPHP($plugin_file);
    $this->assertDrupalCodingStandards($plugin_file);

    $this->parseCode($plugin_file);
    $this->assertHasClass('Drupal\generated_module\Plugin\Block\Alpha');
    $this->assertClassHasParent('Drupal\Core\Block\BlockBase');

    // Check the test module plugin file.
    $test_plugin_file = $files["tests/modules/test_module/src/Plugin/Block/Alpha.php"];
    $this->assertWellFormedPHP($test_plugin_file);
    $this->assertDrupalCodingStandards($test_plugin_file);

    $this->parseCode($test_plugin_file);
    $this->assertHasClass('Drupal\test_module\Plugin\Block\Alpha');
    $this->assertClassHasParent('Drupal\Core\Block\BlockBase');

    $test_file = $files["tests/src/MyTest.php"];

    $this->parseCode($test_file);
    $this->assertHasClass('Drupal\Tests\generated_module\MyTest');
    $expected_modules_property_value = [
      'system',
      'user',
      'generated_module',
      'test_module',
    ];
    $this->assertClassHasPublicProperty('modules', 'array', $expected_modules_property_value);
  }

}
