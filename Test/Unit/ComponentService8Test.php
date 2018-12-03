<?php

namespace DrupalCodeBuilder\Test\Unit;

use \DrupalCodeBuilder\Exception\InvalidInputException;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

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
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => array(
        0 => [
          'service_name' => 'my_service',
        ],
        1 => [
          'service_name' => 'my_other_service',
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(4, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.services.yml", $files, "The files list has a services yml file.");
    $this->assertArrayHasKey("src/MyService.php", $files, "The files list has a service class file.");
    $this->assertArrayHasKey("src/MyOtherService.php", $files, "The files list has a service class file.");

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "$module_name.my_service"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_service", 'class'], "Drupal\\$module_name\\MyService");

    $service_class_file = $files["src/MyService.php"];

    $php_tester = new PHPTester($service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\MyService');
  }

  /**
   * Test generating a module with a service using a preset.
   */
  public function testServiceGenerationFromPreset() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => array(
        0 => [
          'service_tag_type' => 'breadcrumb_builder',
          // TODO: remove once the 'suggest' preset info is live.
          'service_name' => 'breadcrumb_builder',
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertArrayHasKey("$module_name.services.yml", $files, "The files list has a services yml file.");
    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "$module_name.breadcrumb_builder"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.breadcrumb_builder", 'tags', 0, 'name'], 'breadcrumb_builder');

    // The tags property is inlined.
    $yaml_tester->assertPropertyIsExpanded(['services', "$module_name.breadcrumb_builder", 'tags']);
    $yaml_tester->assertPropertyIsExpanded(['services', "$module_name.breadcrumb_builder", 'tags', 0]);
    $yaml_tester->assertPropertyIsInlined(['services', "$module_name.breadcrumb_builder", 'tags', 0, 'name']);

    $this->assertArrayHasKey("src/BreadcrumbBuilder.php", $files, "The files list has a service class file.");
    $service_class_file = $files["src/BreadcrumbBuilder.php"];

    $php_tester = new PHPTester($service_class_file);
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
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => array(
        0 => [
          'service_tag_type' => 'event_subscriber',
          // TODO: remove once the 'suggest' preset info is live.
          'service_name' => 'event_subscriber',
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertArrayHasKey("$module_name.services.yml", $files, "The files list has a services yml file.");
    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', 'test_module.event_subscriber']);
    $yaml_tester->assertPropertyHasValue(['services', 'test_module.event_subscriber', 'class'], 'Drupal\test_module\EventSubscriber\EventSubscriber');
    $yaml_tester->assertHasProperty(['services', 'test_module.event_subscriber', 'tags']);
    $yaml_tester->assertHasProperty(['services', 'test_module.event_subscriber', 'tags', 0]);
    $yaml_tester->assertPropertyHasValue(['services', 'test_module.event_subscriber', 'tags', 0, 'name'], 'event_subscriber');
    $yaml_tester->assertPropertyHasValue(['services', 'test_module.event_subscriber', 'tags', 0, 'priority'], 0);

    $this->assertArrayHasKey("src/EventSubscriber/EventSubscriber.php", $files, "The files list has a service class file.");
    $service_class_file = $files["src/EventSubscriber/EventSubscriber.php"];

    $php_tester = new PHPTester($service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\EventSubscriber\EventSubscriber');
    $php_tester->assertClassHasInterfaces(['Symfony\Component\EventDispatcher\EventSubscriberInterface']);

    // Interface methods.
    $php_tester->assertHasMethod('getSubscribedEvents');
  }

  /**
   * Test a service with with injected services.
   *
   * @group di
   */
  function testServiceGenerationWithServices() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => array(
        0 => [
          'service_name' => 'my_service',
          'injected_services' => [
            'current_user',
            'entity_type.manager',
          ],
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(3, $files, "Three files are returned.");

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.services.yml", $files, "The files list has a services yml file.");
    $this->assertArrayHasKey("src/MyService.php", $files, "The files list has a service class file.");

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "$module_name.my_service"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_service", 'class'], "Drupal\\$module_name\\MyService");
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_service", 'arguments', 0], '@current_user');
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_service", 'arguments', 1], '@entity_type.manager');

    $service_class_file = $files["src/MyService.php"];

    $php_tester = new PHPTester($service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\MyService');

    // Check service injection.
    $php_tester->assertInjectedServices([
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
    ]);
  }

  /**
   * Test a service with with a non-existent injected service.
   */
  function testServiceGenerationWithBadService() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => array(
        0 => [
          'service_name' => 'my_service',
          'injected_services' => [
            'entity_type.manager',
            'made_up',
          ],
        ],
      ),
      'readme' => FALSE,
    );

    $this->expectException(InvalidInputException::class);

    $files = $this->generateModuleFiles($module_data);
  }

  /**
   * Test generating a module with a service, specifying extra parameters.
   */
  public function testServiceGenerationWithParameters() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => array(
        0 => [
          'service_name' => 'my_service',
          // Properties that requesters can specify.
          'prefixed_service_name' => 'my_prefix.my_service',
          'relative_class_name' => ['MyServiceClass'],
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(3, $files, "Three files are returned.");

    $this->assertArrayHasKey("src/MyServiceClass.php", $files, "The service class file has the specified name.");

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "my_prefix.my_service"]);
    $yaml_tester->assertPropertyHasValue(['services', "my_prefix.my_service", 'class'], "Drupal\\$module_name\\MyServiceClass");

    $service_class_file = $files["src/MyServiceClass.php"];

    $php_tester = new PHPTester($service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\MyServiceClass');
  }

  /**
   * Tests the right levels of YAML are inlined.
   */
  public function testServiceYamlFormatting() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'services' => array(
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
      ),
      'readme' => FALSE,
    );

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

}
