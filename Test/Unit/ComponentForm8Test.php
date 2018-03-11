<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests for Form component.
 */
class ComponentForm8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test generating a module with form.
   */
  public function testBasicFormGeneration() {
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

    $php_tester = new PHPTester($form_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Form\MyForm');
    $php_tester->assertClassHasParent('Drupal\Core\Form\FormBase');

    $method_tester = $php_tester->getMethodTester('getFormId');
    $method_tester->assertMethodDocblockHasInheritdoc();
    $method_tester->assertReturnsString('test_module_my_form');
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
            'entity_type.manager',
          ],
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "Two files are returned.");

    $form_file = $files["src/Form/MyForm.php"];

    $php_tester = new PHPTester($form_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Form\MyForm');
    $php_tester->assertClassHasParent('Drupal\Core\Form\FormBase');

    // Check service injection.
    $php_tester->assertInjectedServicesWithFactory([
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

}
