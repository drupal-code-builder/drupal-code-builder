<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Fixtures\File\MockableExtension;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests the PHPUnit test class generator.
 */
class ComponentTestsPHPUnit10Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 10;

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
        'project:dependency_two',
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
      'README.md',
      "tests/src/Kernel/MyTest.php"
    ], $files);

    $this->assertArrayHasKey("tests/src/Kernel/MyTest.php", $files, "The files list has a test class file.");

    $test_file = $files["tests/src/Kernel/MyTest.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $test_file);
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
    $php_tester->assertStatementIsParentCall('setUp', 0);
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

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\Unit\MyTest');
    $php_tester->assertClassHasParent('Drupal\Tests\UnitTestCase');
    $php_tester->assertNotClassHasProperty('modules');
    $php_tester->assertHasMethodOrder(['setUp', 'testMyTest']);
    $php_tester->assertStatementIsParentCall('setUp', 0);
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

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\Kernel\MyTest');
    $php_tester->assertClassHasParent('Drupal\KernelTests\KernelTestBase');
    $php_tester->assertClassHasProtectedProperty('modules', 'array', [
      'system',
      'user',
      'test_module',
    ]);
    $php_tester->assertHasMethodOrder(['setUp', 'testMyTest']);
    $php_tester->assertStatementIsParentCall('setUp', 0);
    $setup_method_tester = $php_tester->getMethodTester('setUp');
    $setup_method_tester->assertReturnType('void');
  }

  /**
   * Create a basic browser test.
   */
  function testBrowserTest() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Generated module',
      'phpunit_tests' => [
        0 => [
          'test_type' => 'browser',
          'plain_class_name' => 'MyTest',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "test_module.info.yml",
      "tests/src/Functional/MyTest.php"
    ], $files);

    $test_file = $files["tests/src/Functional/MyTest.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\Functional\MyTest');
    $php_tester->assertClassHasParent('Drupal\Tests\BrowserTestBase');
    $php_tester->assertClassHasProtectedProperty('modules', 'array', [
      'system',
      'user',
      'test_module',
    ]);
    $php_tester->assertClassHasProtectedProperty('defaultTheme', NULL, 'stark');
    $php_tester->assertHasMethodOrder(['setUp', 'testMyTest']);
    $php_tester->assertStatementIsParentCall('setUp', 0);
    $setup_method_tester = $php_tester->getMethodTester('setUp');
    $setup_method_tester->assertReturnType('void');
  }

  /**
   * Create a basic JavaScript test.
   */
  function testJavaScriptTest() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Generated module',
      'phpunit_tests' => [
        0 => [
          'test_type' => 'javascript',
          'plain_class_name' => 'MyTest',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "test_module.info.yml",
      "tests/src/FunctionalJavascript/MyTest.php"
    ], $files);

    $test_file = $files["tests/src/FunctionalJavascript/MyTest.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\FunctionalJavascript\MyTest');
    $php_tester->assertClassHasParent('Drupal\FunctionalJavascriptTests\WebDriverTestBase');
    $php_tester->assertClassHasProtectedProperty('modules', 'array', [
      'system',
      'user',
      'test_module',
    ]);
    $php_tester->assertClassHasProtectedProperty('defaultTheme', NULL, 'stark');
    $php_tester->assertHasMethodOrder(['setUp', 'testMyTest']);
    $php_tester->assertStatementIsParentCall('setUp', 0);
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

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $test_file);
    $php_tester->assertDrupalCodingStandards($this->phpcsExcludedSniffs);
    $php_tester->assertHasClass('Drupal\Tests\test_module\Kernel\MyTest');
    $php_tester->assertClassHasParent('Drupal\KernelTests\KernelTestBase');
    $php_tester->assertHasMethodOrder(['setUp', 'testMyTest']);
    $php_tester->assertStatementIsParentCall('setUp', 0);

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
              'services' => [
                [
                  'service_name' => 'test_service',
                  'injected_services' => [
                    'entity_type.manager',
                  ],
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
      'services' => [
        [
          'service_name' => 'my_service',
          'injected_services' => [
            'entity_type.manager',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'generated_module.info.yml',
      'generated_module.services.yml',
      'src/MyService.php',
      'src/Plugin/Block/Alpha.php',
      'config/schema/generated_module.schema.yml',
      'tests/src/Kernel/MyTest.php',
      'tests/modules/my_test/my_test.info.yml',
      'tests/modules/my_test/my_test.services.yml',
      'tests/modules/my_test/src/TestService.php',
      'tests/modules/my_test/src/Plugin/Block/Alpha.php',
      'tests/modules/my_test/config/schema/my_test.schema.yml',
    ], $files);

    // Check the main module .info file.
    $info_file = $files["generated_module.info.yml"];

    $yaml_tester = new YamlTester($info_file);
    $yaml_tester->assertPropertyHasValue('name', 'Generated module');
    $yaml_tester->assertPropertyHasValue('type', 'module');
    $yaml_tester->assertPropertyHasValue('core_version_requirement', '^8 || ^9 || ^10');

    // Check the test module .info file.
    $test_module_info_file = $files['tests/modules/my_test/my_test.info.yml'];

    $yaml_tester = new YamlTester($test_module_info_file);
    $yaml_tester->assertPropertyHasValue('name', 'My Test');
    $yaml_tester->assertPropertyHasValue('type', 'module');
    $yaml_tester->assertPropertyHasValue('package', 'Testing');
    $yaml_tester->assertPropertyHasValue('core_version_requirement', '^8 || ^9 || ^10');

    // Check the main module plugin file.
    $plugin_file = $files["src/Plugin/Block/Alpha.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\generated_module\Plugin\Block\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Block\BlockBase');

    // Check the test module plugin file.
    $test_plugin_file = $files["tests/modules/my_test/src/Plugin/Block/Alpha.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $test_plugin_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\my_test\Plugin\Block\Alpha');
    $php_tester->assertClassHasParent('Drupal\Core\Block\BlockBase');

    // Check the main module service.
    $service_class_file = $files["src/MyService.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\generated_module\MyService');
    $php_tester->assertOnlyInjectedServices([
      [
        'typehint' => 'Drupal\Core\Entity\EntityTypeManagerInterface',
        'service_name' => 'entity_type.manager',
        'property_name' => 'entityTypeManager',
        'parameter_name' => 'entity_type_manager',
      ],
    ]);

    // Check the test module service.
    $service_class_file = $files["tests/modules/my_test/src/TestService.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\my_test\TestService');
    $php_tester->assertOnlyInjectedServices([
      [
        'typehint' => 'Drupal\Core\Entity\EntityTypeManagerInterface',
        'service_name' => 'entity_type.manager',
        'property_name' => 'entityTypeManager',
        'parameter_name' => 'entity_type_manager',
      ],
    ]);

    $test_file = $files["tests/src/Kernel/MyTest.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $test_file);
    $php_tester->assertHasClass('Drupal\Tests\generated_module\Kernel\MyTest');
    $expected_modules_property_value = [
      'system',
      'user',
      'generated_module',
      'my_test',
    ];
    $php_tester->assertClassHasProtectedProperty('modules', 'array', $expected_modules_property_value);
  }

  /**
   * Data provider.
   *
   * Test data for:
   *  - testTestModuleWithExistingFunctions()
   *  - testTestModuleWithExistingServices()
   */
  public function dataTestModuleWithExistingFunctions() {
    $data = [];

    $options = [
      'main',
      'test',
    ];

    foreach ($options as $option) {
      foreach ($options as $option_inner) {
         $data["$option-$option_inner"] = [
            'generated' => $option,
            'existing_code' => $option_inner
         ];
      }
    }

    return $data;
  }

  /**
   * Tests existing functions in the module files.
   *
   * This generates a hook in either the main or the test module, with an
   * existing function is either the main or test module file.
   *
   * @group existing
   * @group test
   *
   * @dataProvider dataTestModuleWithExistingFunctions
   *
   * @param string $generated_hook
   *   Where the generated hook goes. One of:
   *    - 'main': The main generated module.
   *    - 'test': The test module.
   * @param string $existing_code
   *   Where the existing code goes. One of:
   *    - 'main': The main generated module.
   *    - 'test': The test module.
   */
  public function testTestModuleWithExistingFunctions(string $generated_hook, string $existing_code) {
    // Create a module.
    $module_data = [
      'base' => 'module',
      'root_name' => 'generated_module',
      'readable_name' => 'Generated module',
      'hooks' => match ($generated_hook) {
        'main' => ['hook_form_alter'],
        'test' => [],
      },
      'phpunit_tests' => [
        0 => [
          'test_type' => 'kernel',
          'plain_class_name' => 'MyTest',
          'test_modules' => [
            0 => [
              // Don't specify root_name so the default is applied.
              'hooks' => match ($generated_hook) {
                'main' => [],
                'test' => ['hook_form_alter'],
              },
            ],
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $extension = new MockableExtension('module', __DIR__ . '/../Fixtures/modules/existing/');
    $extension->mockInfoFile('generated_module');

    $existing_function_name = match ($existing_code) {
      'main' => 'generated_module_my_function',
      'test' => 'my_test_my_function',
    };

    $existing_module_file = <<<EOPHP
      <?php

      /**
       * @file
       * Contains hooks for the Generated Module module.
       */

      /**
       * Some function.
       */
      function $existing_function_name() {
        // Code does a thing.
        return 42;
      }

      EOPHP;

    $extension->setFile(match ($existing_code) {
      'main' => 'generated_module.module',
      'test' => 'tests/modules/my_test/my_test.module',
    }, $existing_module_file);

    $files = $this->generateModuleFiles($module_data, $extension);

    if ($generated_hook == 'main') {
      $this->assertArrayHasKey('generated_module.module', $files);
    }
    else {
      $this->assertArrayNotHasKey('generated_module.module', $files);
    }

    if ($generated_hook == 'test') {
      $this->assertArrayHasKey('tests/modules/my_test/my_test.module', $files);
    }
    else {
      $this->assertArrayNotHasKey('tests/modules/my_test/my_test.module', $files);
    }

    // In all cases, only one module file is generated.
    $module_file = match ($generated_hook) {
      'main' => $files['generated_module.module'],
      'test' => $files['tests/modules/my_test/my_test.module'],
    };

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $module_file);
    $phpcs_excluded_sniffs = [
      // Temporarily exclude the sniff for comment lines being too long, as a
      // comment in hook_form_alter() violates this. TODO: remove this when
      // https://www.drupal.org/project/drupal/issues/2924184 is fixed.
      'Drupal.Files.LineLength.TooLong',
    ];
    $php_tester->assertDrupalCodingStandards($phpcs_excluded_sniffs);
    $php_tester->assertHasHookImplementation('hook_form_alter', match ($generated_hook) {
      'main' => 'generated_module',
      'test' => 'my_test',
    });

    // If the existing function was in the same module as where we're generating
    // a hook, the function should have been merged.
    if ($generated_hook == $existing_code) {
      $php_tester->assertHasFunction($existing_function_name);
    }
    else {
      $php_tester->assertNotHasFunction($existing_function_name);
    }
  }

  /**
   * Tests existing services with test module.
   *
   * This generates a service in either the main or the test module, with an
   * existing service is either the main or test module.
   *
   * @group existing
   * @group test
   *
   * @dataProvider dataTestModuleWithExistingFunctions
   *
   * @param string $generated_service
   *   Where the generated service goes. One of:
   *    - 'main': The main generated module.
   *    - 'test': The test module.
   * @param string $existing_code
   *   Where the existing code goes. One of:
   *    - 'main': The main generated module.
   *    - 'test': The test module.
   */
  public function testTestModuleWithExistingServices(string $generated_service, string $existing_code) {
    $services_value = [
      [
        'service_name' => 'test_service',
        'injected_services' => [
          'current_user',
          'entity_type.manager',
        ],
      ]
    ];

    // Create a module.
    $module_data = [
      'base' => 'module',
      'root_name' => 'generated_module',
      'readable_name' => 'Main module',
      'services' => match ($generated_service) {
        'main' => $services_value,
        'test' => [],
      },
      'phpunit_tests' => [
        0 => [
          'test_type' => 'kernel',
          'plain_class_name' => 'MyTest',
          'test_modules' => [
            0 => [
              // Don't specify root_name so the default is applied.
              'services' => match ($generated_service) {
                'main' => [],
                'test' => $services_value,
              },
            ],
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $extension = new MockableExtension('module', __DIR__ . '/../Fixtures/modules/existing/');
    $services_file_yaml = <<<EOT
      services:
        existing.alpha:
          class: Drupal\my_module\Alpha
          arguments: ['@current_user', '@entity_type.manager']
      EOT;

    $extension->mockInfoFile('generated_module');
    $extension->mockInfoFile('test_modules', [], 'tests/modules/my_test/');
    $extension->setFile(match ($existing_code) {
      'main' => 'generated_module.services.yml',
      'test' => 'tests/modules/my_test/my_test.services.yml',
    }, $services_file_yaml);

    $files = $this->generateModuleFiles($module_data, $extension);

    if ($generated_service == 'main') {
      $this->assertArrayHasKey('generated_module.services.yml', $files);
    }
    else {
      $this->assertArrayNotHasKey('generated_module.services.yml', $files);
    }

    if ($generated_service == 'test') {
      $this->assertArrayHasKey('tests/modules/my_test/my_test.services.yml', $files);
    }
    else {
      $this->assertArrayNotHasKey('tests/modules/my_test/my_test.services.yml', $files);
    }

    // In all cases, only one services file is generated.
    $services_file = match ($generated_service) {
      'main' => $files['generated_module.services.yml'],
      'test' => $files['tests/modules/my_test/my_test.services.yml'],
    };

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');

    $yaml_tester->assertHasProperty(['services', match ($generated_service) {
      'main' => 'generated_module.test_service',
      'test' => 'my_test.test_service',
    }]);

    // If the existing function was in the same module as where we're generating
    // a hook, the function should have been merged.
    if ($generated_service == $existing_code) {
      $yaml_tester->assertHasProperty(['services', 'existing.alpha']);
    }
    else {
      $yaml_tester->assertNotHasProperty(['services', "existing.alpha"]);
    }
  }
}
