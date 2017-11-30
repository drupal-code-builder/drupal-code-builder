<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests for Service component.
 */
class ComponentService8Test extends TestBaseComponentGeneration {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test generating a module with a service.
   */
  public function testServiceGeneration() {
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

    $this->assertYamlProperty($services_file, 'services', NULL, "The services file has the services property.");
    $this->assertYamlProperty($services_file, "$module_name.my_service", NULL, "The services file declares the service name.");
    $this->assertYamlProperty($services_file, 'class', "Drupal\\$module_name\\MyService", "The services file declares the service class.");

    $service_class_file = $files["src/MyService.php"];

    $this->assertWellFormedPHP($service_class_file);
    $this->assertDrupalCodingStandards($service_class_file);
    $this->assertNoTrailingWhitespace($service_class_file, "The service class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($service_class_file);

    $this->assertNamespace(['Drupal', $module_name], $service_class_file, "The service class file contains contains the expected namespace.");
    $this->assertClass('MyService', $service_class_file, "The service file contains the service class.");
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

    // TODO: assertYamlProperty() is not powerful enough for this.
    //$this->assertYamlProperty($services_file, 'tags', [], "The services file declares the service class.");
    // Quick hack in the meantime.
    $this->assertContains('name: breadcrumb_builder', $services_file);

    $this->assertArrayHasKey("src/BreadcrumbBuilder.php", $files, "The files list has a service class file.");
    $service_class_file = $files["src/BreadcrumbBuilder.php"];

    $this->assertWellFormedPHP($service_class_file);
    $this->assertDrupalCodingStandards($service_class_file);

    // Interface methods.
    $this->assertMethod('applies', $service_class_file);
    $this->assertMethod('build', $service_class_file);
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

    // TODO: assertYamlProperty() is not powerful enough for this.
    //$this->assertYamlProperty($services_file, 'tags', [], "The services file declares the service class.");
    // Quick hack in the meantime.
    $this->assertContains('name: event_subscriber', $services_file);

    $this->assertArrayHasKey("src/EventSubscriber/EventSubscriber.php", $files, "The files list has a service class file.");
    $service_class_file = $files["src/EventSubscriber/EventSubscriber.php"];

    $this->assertWellFormedPHP($service_class_file);
    $this->assertDrupalCodingStandards($service_class_file);

    // Interface methods.
    $this->assertMethod('getSubscribedEvents', $service_class_file);
  }

  /**
   * Test a service with with injected services.
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

    $this->assertYamlProperty($services_file, 'services', NULL, "The services file has the services property.");
    $this->assertYamlProperty($services_file, "$module_name.my_service", NULL, "The services file declares the service name.");
    $this->assertYamlProperty($services_file, 'class', "Drupal\\$module_name\\MyService", "The services file declares the service class.");
    // TODO: check service argument is present.

    $service_class_file = $files["src/MyService.php"];

    $this->assertWellFormedPHP($service_class_file);
    $this->assertDrupalCodingStandards($service_class_file);

    $this->parseCode($service_class_file);
    $this->assertHasClass('Drupal\test_module\MyService');

    // Check service injection.
    $this->assertInjectedServices([
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
          // Non-declared properties (for now!) that requesters can specify.
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

    $this->assertYamlProperty($services_file, 'services', NULL, "The services file has the services property.");
    $this->assertYamlProperty($services_file, "my_prefix.my_service", NULL, "The services file declares the specified service name.");
    $this->assertYamlProperty($services_file, 'class', "Drupal\\$module_name\\MyServiceClass", "The services file declares the service class.");

    $service_class_file = $files["src/MyServiceClass.php"];

    $this->assertWellFormedPHP($service_class_file);
    $this->assertDrupalCodingStandards($service_class_file);

    $this->assertNamespace(['Drupal', $module_name], $service_class_file, "The service class file contains contains the expected namespace.");
    $this->assertClass('MyServiceClass', $service_class_file, "The service file contains the service class.");
  }

}
