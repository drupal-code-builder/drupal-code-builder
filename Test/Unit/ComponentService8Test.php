<?php

/**
 * @file
 * Contains ComponentService8Test.
 */

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests for Service component.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/ComponentService8Test.php
 * @endcode
 */
class ComponentService8Test extends TestBase {

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
          'injected_services' => [],
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

    $service_class_file = $files["src/MyService.php"];

    $this->assertNoTrailingWhitespace($service_class_file, "The service class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($service_class_file);

    $this->assertNamespace(['Drupal', $module_name], $service_class_file, "The service class file contains contains the expected namespace.");
    $this->assertClass('MyService', $service_class_file, "The service file contains the service class.");
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

    // Check the injected service.
    $this->assertClassProperty('currentUser', $service_class_file, "The service class has a property for the injected service.");

    $this->assertMethod('__construct', $service_class_file, "The service class has a constructor method.");
    $parameters = [
      'current_user',
    ];
    $this->assertFunctionHasParameters('__construct', $parameters, $service_class_file);
    $this->assertFunctionCode($service_class_file, '__construct', '$this->currentUser = $current_user;');
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
          'injected_services' => [],
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

    $this->assertNamespace(['Drupal', $module_name], $service_class_file, "The service class file contains contains the expected namespace.");
    $this->assertClass('MyServiceClass', $service_class_file, "The service file contains the service class.");
  }

}
