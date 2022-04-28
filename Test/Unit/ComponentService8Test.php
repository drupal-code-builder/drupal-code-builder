<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Fixtures\File\MockableExtension;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;
use MutableTypedData\Exception\InvalidInputException;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for Service component.
 *
 * @group yaml
 */
class ComponentService8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test generating a module with a service.
   */
  public function testBasicServiceGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          'service_name' => 'my_service',
        ],
        1 => [
          'service_name' => 'my_other_service',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.services.yml',
      "src/MyService.php",
      "src/MyOtherService.php",
    ], $files);

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "$module_name.my_service"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_service", 'class'], "Drupal\\$module_name\\MyService");
    $yaml_tester->assertHasProperty(['services', "$module_name.my_other_service"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_other_service", 'class'], "Drupal\\$module_name\\MyOtherService");

    $service_class_file = $files["src/MyService.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\MyService');
  }

  /**
   * Test namespace configuration for service generation.
   *
   * @group config
   */
  public function testServiceGenerationNamespaceConfiguration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          'service_name' => 'my_service',
        ],
        1 => [
          'service_name' => 'my_other_service',
        ],
      ],
      'readme' => FALSE,
      'configuration' => [
        'service_namespace' => 'Cake',
      ],
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.services.yml',
      "src/Cake/MyService.php",
      "src/Cake/MyOtherService.php",
    ], $files);

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "$module_name.my_service"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_service", 'class'], "Drupal\\$module_name\\Cake\\MyService");
    $yaml_tester->assertHasProperty(['services', "$module_name.my_other_service"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_other_service", 'class'], "Drupal\\$module_name\\Cake\\MyOtherService");

    $service_class_file = $files["src/Cake/MyService.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Cake\MyService');

    // Test with an empty string for the configuration value.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          'service_name' => 'my_service',
        ],
        1 => [
          'service_name' => 'my_other_service',
        ],
      ],
      'readme' => FALSE,
      'configuration' => [
        'service_namespace' => '',
      ],
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.services.yml',
      "src/MyService.php",
      "src/MyOtherService.php",
    ], $files);
  }

  /**
   * Test YAML linebreaks configuration for service generation.
   *
   * @group config
   */
  public function testServiceGenerationYamlLinebreaksConfiguration() {
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          'service_name' => 'my_service',
        ],
        1 => [
          'service_name' => 'my_other_service',
        ],
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $services_file = $files['test_module.services.yml'];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertPropertyHasNoBlankLineBefore(['services', 'test_module.my_other_service']);

    $module_data['configuration'] = [
      'service_linebreaks' => TRUE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $services_file = $files['test_module.services.yml'];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertPropertyHasBlankLineBefore(['services', 'test_module.my_other_service']);
  }

  /**
   * Test service parameter expansion configuration.
   *
   * @group config
   */
  public function testServiceGenerationYamlParameterLinebreaksConfiguration() {
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          'service_name' => 'my_service',
          'injected_services' => [
            'current_user',
            'entity_type.manager',
          ],
        ],
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $services_file = $files['test_module.services.yml'];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertPropertyIsInlined(['services', 'test_module.my_service', 'arguments', 0]);
    $yaml_tester->assertPropertyIsInlined(['services', 'test_module.my_service', 'arguments', 1]);

    $module_data['configuration'] = [
      'service_parameters_linebreaks' => TRUE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $services_file = $files['test_module.services.yml'];

    $yaml_tester = new YamlTester($services_file);

    $yaml_tester->assertPropertyIsExpanded(['services', 'test_module.my_service', 'arguments', 0]);
    $yaml_tester->assertPropertyIsExpanded(['services', 'test_module.my_service', 'arguments', 1]);
  }

  /**
   * Test generating a module with a service using a preset.
   *
   * @group presets
   */
  public function testServiceGenerationFromPreset() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          'service_tag_type' => 'breadcrumb_builder',
          // TODO: remove once the 'suggest' preset info is live.
          'service_name' => 'breadcrumb_builder',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.services.yml',
      "src/BreadcrumbBuilder.php",
    ], $files);

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "$module_name.breadcrumb_builder"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.breadcrumb_builder", 'tags', 0, 'name'], 'breadcrumb_builder');

    // The tags property is inlined.
    $yaml_tester->assertPropertyIsExpanded(['services', "$module_name.breadcrumb_builder", 'tags']);
    $yaml_tester->assertPropertyIsExpanded(['services', "$module_name.breadcrumb_builder", 'tags', 0]);
    $yaml_tester->assertPropertyIsInlined(['services', "$module_name.breadcrumb_builder", 'tags', 0, 'name']);

    $service_class_file = $files["src/BreadcrumbBuilder.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\BreadcrumbBuilder');
    $php_tester->assertClassHasInterfaces(['Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface']);

    // Interface methods.
    $php_tester->assertHasMethod('applies');
    $php_tester->assertHasMethod('build');
  }

  /**
   * Test generating an event subscriber service.
   */
  public function testServiceGenerationEventSubscriber() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          'service_tag_type' => 'event_subscriber',
          // TODO: remove once the 'suggest' preset info is live.
          'service_name' => 'event_subscriber',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.services.yml',
      "src/EventSubscriber/EventSubscriber.php",
    ], $files);

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', 'test_module.event_subscriber']);
    $yaml_tester->assertPropertyHasValue(['services', 'test_module.event_subscriber', 'class'], 'Drupal\test_module\EventSubscriber\EventSubscriber');
    $yaml_tester->assertHasProperty(['services', 'test_module.event_subscriber', 'tags']);
    $yaml_tester->assertHasProperty(['services', 'test_module.event_subscriber', 'tags', 0]);
    $yaml_tester->assertPropertyHasValue(['services', 'test_module.event_subscriber', 'tags', 0, 'name'], 'event_subscriber');
    $yaml_tester->assertPropertyHasValue(['services', 'test_module.event_subscriber', 'tags', 0, 'priority'], 0);

    $service_class_file = $files["src/EventSubscriber/EventSubscriber.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\EventSubscriber\EventSubscriber');
    $php_tester->assertClassHasInterfaces(['Symfony\Component\EventDispatcher\EventSubscriberInterface']);

    // Interface methods.
    $php_tester->assertHasMethod('getSubscribedEvents');
  }

  /**
   * Data provider for testServiceGenerationWithServices().
   */
  public function providerServiceGenerationWithServices() {
    return [
      // Pseudoservice with the real service also present as a parameter.
      'pseudo-with-real' => [
        '$injected_services' => [
          'current_user',
          'entity_type.manager',
          'storage:node',
        ],
        '$yaml_arguments' => [
          0 => '@current_user',
          1 => '@entity_type.manager',
        ],
        '$assert_injected_services' => [
          [
            'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
            'service_name' => 'current_user',
            'property_name' => 'currentUser',
            'parameter_name' => 'current_user',
          ],
          [
            'typehint' => 'Drupal\Core\Entity\EntityTypeManagerInterface',
            'service_name' => 'entity_type.manager',
            'property_name' => 'entityTypeManager',
            'parameter_name' => 'entity_type_manager',
          ],
          [
            'typehint' => 'Drupal\Core\Entity\EntityStorageInterface',
            'service_name' => 'entity_type.manager',
            'property_name' => 'nodeStorage',
            'parameter_name' => 'entity_type_manager',
            // This isn't passed in as a __construct() parameter.
            'extracted_from_other_service' => TRUE,
            'extraction_method' => 'getStorage',
            'extraction_method_param' => 'node',
          ],
        ],
      ],
      // Pseudoservice without the real service also as a parameter.
      'pseudo-without-real' => [
        '$injected_services' => [
          'current_user',
          'storage:node',
        ],
        '$yaml_arguments' => [
          0 => '@current_user',
          1 => '@entity_type.manager',
        ],
        '$assert_injected_services' => [
          [
            'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
            'service_name' => 'current_user',
            'property_name' => 'currentUser',
            'parameter_name' => 'current_user',
          ],
          [
            'typehint' => 'Drupal\Core\Entity\EntityStorageInterface',
            'service_name' => 'entity_type.manager',
            'property_name' => 'nodeStorage',
            'parameter_name' => 'entity_type_manager',
            'extraction_method' => 'getStorage',
            'extraction_method_param' => 'node',
          ],
        ],
      ],
      'pseudo-only' => [
        '$injected_services' => [
          'storage:node',
        ],
        '$yaml_arguments' => [
          0 => '@entity_type.manager',
        ],
        '$assert_injected_services' => [
          [
            'typehint' => 'Drupal\Core\Entity\EntityStorageInterface',
            'service_name' => 'entity_type.manager',
            'property_name' => 'nodeStorage',
            'parameter_name' => 'entity_type_manager',
            'extraction_method' => 'getStorage',
            'extraction_method_param' => 'node',
          ],
        ],
      ],
    ];
  }

  /**
   * Test a service with with injected services.
   *
   * @group di
   *
   * @dataProvider providerServiceGenerationWithServices
   */
  function testServiceGenerationWithServices($injected_services, $yaml_arguments, $assert_injected_services) {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          'service_name' => 'my_service',
          'injected_services' => $injected_services,
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.services.yml',
      "src/MyService.php",
    ], $files);

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.services.yml", $files, "The files list has a services yml file.");
    $this->assertArrayHasKey("src/MyService.php", $files, "The files list has a service class file.");

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "$module_name.my_service"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_service", 'class'], "Drupal\\$module_name\\MyService");
    foreach ($yaml_arguments as $index => $argument) {
      $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_service", 'arguments', $index], $argument);
    }

    $service_class_file = $files["src/MyService.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\MyService');

    // Check service injection.
    $php_tester->assertInjectedServices($assert_injected_services);
  }

  /**
   * Test a service with with a non-existent injected service.
   */
  function testServiceGenerationWithBadService() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          'service_name' => 'my_service',
          'injected_services' => [
            'entity_type.manager',
            'made_up',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $this->expectException(\DrupalCodeBuilder\Test\Exception\ValidationException::class);

    $files = $this->generateModuleFiles($module_data);
  }

  /**
   * Test generating a module with a service, specifying extra parameters.
   */
  public function testServiceGenerationWithParameters() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          'service_name' => 'my_service',
          // Properties that requesters can specify.
          'prefixed_service_name' => 'my_prefix.my_service',
          'plain_class_name' => 'MyServiceClass',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.services.yml',
      "src/MyServiceClass.php",
    ], $files);

    $this->assertArrayHasKey("src/MyServiceClass.php", $files, "The service class file has the specified name.");

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "my_prefix.my_service"]);
    $yaml_tester->assertPropertyHasValue(['services', "my_prefix.my_service", 'class'], "Drupal\\$module_name\\MyServiceClass");

    $service_class_file = $files["src/MyServiceClass.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\MyServiceClass');
  }

  /**
   * Tests the right levels of YAML are inlined.
   */
  public function testServiceYamlFormatting() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => [
        0 => [
          // We want both tags and services, as they need to be inlined at
          // different levels.
          'service_tag_type' => 'breadcrumb_builder',
          'service_name' => 'alpha',
          'injected_services' => [
            'current_user',
          ],
        ],
        1 => [
          'service_tag_type' => 'breadcrumb_builder',
          'service_name' => 'beta',
          'injected_services' => [
            'entity_type.manager',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertArrayHasKey("$module_name.services.yml", $files, "The files list has a services yml file.");
    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "$module_name.alpha"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.alpha", 'tags', 0, 'name'], 'breadcrumb_builder');

    // The arguments property is expanded, and items beneath that are inlined.
    $yaml_tester->assertPropertyIsExpanded(['services', "$module_name.alpha", 'arguments']);
    $yaml_tester->assertPropertyIsInlined(['services', "$module_name.alpha", 'arguments', 0]);
    $yaml_tester->assertPropertyIsExpanded(['services', "$module_name.beta", 'arguments']);
    $yaml_tester->assertPropertyIsInlined(['services', "$module_name.beta", 'arguments', 0]);

    // Each tag is expanded, and properties of a tag are inlined.
    $yaml_tester->assertPropertyIsExpanded(['services', "$module_name.alpha", 'tags']);
    $yaml_tester->assertPropertyIsExpanded(['services', "$module_name.alpha", 'tags', 0]);
    $yaml_tester->assertPropertyIsInlined(['services', "$module_name.alpha", 'tags', 0, 'name']);
    $yaml_tester->assertPropertyIsExpanded(['services', "$module_name.beta", 'tags']);
    $yaml_tester->assertPropertyIsExpanded(['services', "$module_name.beta", 'tags', 0]);
    $yaml_tester->assertPropertyIsInlined(['services', "$module_name.beta", 'tags', 0, 'name']);
  }

  /**
   * Data provider for testExistingServicesYamlFile().
   */
  public function dataExistingServicesYamlFile() {
    return [
      'only-existing' => [
        <<<EOT
          existing-service:
            class: Drupal\my_module\Existing
            arguments: ['@current_user', '@entity_type.manager']
            tags:
              - { name: normalizer, priority: 10 }
        EOT,
        [],
        NULL,
      ],
      'only-generated' => [
        NULL,
        [
          'service_name' => 'alpha',
          'injected_services' => [
            'current_user',
          ],
        ],
        <<<'EOT'
          existing.alpha:
            class: Drupal\existing\Alpha
            arguments: ['@current_user']
        EOT,
      ],
      'both-distinct' => [
        <<<EOT
          existing-service:
            class: Drupal\my_module\Existing
            arguments: ['@current_user', '@entity_type.manager']
            tags:
              - { name: normalizer, priority: 10 }
        EOT,
        [
          'service_name' => 'alpha',
          'injected_services' => [
            'current_user',
          ],
        ],
        <<<'EOT'
          existing.alpha:
            class: Drupal\existing\Alpha
            arguments: ['@current_user']
        EOT,
      ],
      'both-merge' => [
        <<<EOT
          existing.alpha:
            class: Drupal\my_module\Alpha
            arguments: ['@current_user', '@entity_type.manager']
        EOT,
        [
          'service_name' => 'alpha',
          'injected_services' => [
            'current_user',
            'module_handler',
          ],
        ],
        <<<'EOT'
          existing.alpha:
            class: Drupal\existing\Alpha
            arguments: ['@current_user', '@entity_type.manager', '@module_handler']
        EOT,
      ],
    ];
  }

  /**
   * Tests with an existing services file.
   *
   * @param string|null $existing
   *   The YAML defining the list of existing services, without the initial
   *   'services' key. NULL to have no existing services.yml file.
   * @param array|null $generated
   *   The array of data for a single service to generate. NULL to generate no
   *   services.
   * @param string|null $resulting
   *   The expected YAML defining the list of existing services, without the
   *   initial 'services' key. NULL if no file is expected to be generated.
   *
   * @group existing
   *
   * @dataProvider dataExistingServicesYamlFile
   *
   */
  public function testExistingServicesYamlFile(?string $existing, ?array $generated, ?string $resulting) {
    $module_name = 'existing';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'module_package' => 'Test Package',
      'readme' => FALSE,
    ];
    if ($generated) {
      $module_data['services'] = [
        0 => $generated,
      ];
    }

    $extension = new MockableExtension('module', __DIR__ . '/../Fixtures/modules/existing/');

    if (!is_null($existing)) {
      $services_file_yaml = <<<EOT
        services:
        $existing
        EOT;

      $extension->setFile('%module.services.yml', $services_file_yaml);
    }

    $files = $this->generateModuleFiles($module_data, $extension);

    // A NULL for the expected resulting file means we don't expect the file to
    // be generated at all.
    if (is_null($resulting)) {
      $this->assertArrayNotHasKey("$module_name.services.yml", $files);
      return;
    }

    $services_file = $files["$module_name.services.yml"];

    // Don't use the YamlTester, we need to check the whole thing against the
    // parameter.
    $this->assertStringContainsString($resulting, $services_file);
  }

  /**
   * Test merging of injected services in an existing service.
   */
  public function testExistingServiceMerge() {
    $module_name = 'existing';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'module_package' => 'Test Package',
      'readme' => FALSE,
      'services' => [
        0 => [
          'service_name' => 'alpha',
          'injected_services' => [
            'current_user',
            'module_handler',
          ],
        ],
      ],
    ];

    $extension = new MockableExtension('module', __DIR__ . '/../Fixtures/modules/existing/');
    $services_file_yaml = <<<EOT
      services:
        existing.alpha:
          class: Drupal\my_module\Alpha
          arguments: ['@current_user', '@entity_type.manager']
      EOT;

    $extension->setFile('%module.services.yml', $services_file_yaml);

    $files = $this->generateModuleFiles($module_data, $extension);

    $services_file = $files["$module_name.services.yml"];
    $yaml_tester = new YamlTester($services_file);

    // We expect the order to be existing services first, in their original
    // order, and then new ones from the generate request.
    $yaml_arguments = [
      '@current_user',
      '@entity_type.manager',
      '@module_handler',
    ];
    foreach ($yaml_arguments as $index => $argument) {
      $yaml_tester->assertPropertyHasValue(['services', "$module_name.alpha", 'arguments', $index], $argument);
    }

    $service_class_file = $files['src/Alpha.php'];

    $php_tester = new PHPTester($this->drupalMajorVersion, $service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\existing\Alpha');

    $assert_injected_services = [
      [
        'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
        'service_name' => 'current_user',
        'property_name' => 'currentUser',
        'parameter_name' => 'current_user',
      ],
      [
        'typehint' => 'Drupal\Core\Entity\EntityTypeManagerInterface',
        'service_name' => 'entity_type.manager',
        'property_name' => 'entityTypeManager',
        'parameter_name' => 'entity_type_manager',
      ],
      [
        'typehint' => 'Drupal\Core\Extension\ModuleHandlerInterface',
        'service_name' => 'module_handler',
        'property_name' => 'moduleHandler',
        'parameter_name' => 'module_handler',
      ],
    ];
    $php_tester->assertInjectedServices($assert_injected_services);
  }

}
