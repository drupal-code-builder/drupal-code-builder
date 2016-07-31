<?php

/**
 * @file
 * Contains ComponentForm8Test.
 */

namespace DrupalCodeBuilder\Test;

/**
 * Tests for Form component.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/ComponentForm8Test.php
 * @endcode
 */
class ComponentForm8Test extends TestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test generating a module with form.
   */
  public function testFormGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'forms' => array(
        0 => [
          'form_class_name' => 'MyForm',
          'injected_services' => [],
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "Two files are returned.");

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("src/Form/MyForm.php", $files, "The files list has a form class file.");

    $form_file = $files["src/Form/MyForm.php"];
    $this->assertNoTrailingWhitespace($form_file);

    $this->assertNoTrailingWhitespace($form_file, "The form class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($form_file);

    $this->assertNamespace(['Drupal', $module_name, 'Form'], $form_file, "The form class file contains contains the expected namespace.");
    $this->assertClass('MyForm', $form_file, "The form file contains the form class.");

    foreach (['getFormId', 'buildForm', 'submitForm'] as $method) {
      $this->assertMethod($method, $form_file, "The form file contains the $method method.");
    }

    // TODO: should be 'my_form' instead. Bug!
    $this->assertFunctionCode($form_file, 'getFormId', "return 'test_module_myform");
  }

  /**
   * Test Form component with injected services.
   */
  function testFormGenerationWithServices() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'forms' => array(
        0 => [
          'form_class_name' => 'MyForm',
          'injected_services' => [
            'current_user',
          ],
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "Two files are returned.");

    $form_file = $files["src/Form/MyForm.php"];

    $this->assertClassImport(['Symfony', 'Component', 'DependencyInjection', 'ContainerInterface'], $form_file);
    $this->assertClassImport(['Drupal', 'Core', 'Session', 'AccountProxyInterface'], $form_file);

    $this->assertMethod('__construct', $form_file, "The form class has a constructor method.");
    $this->assertMethod('create', $form_file, "The form class has a create method.");
  }

}
