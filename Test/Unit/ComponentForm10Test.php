<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests for Form component.
 *
 * @group form
 */
class ComponentForm10Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 10;

  /**
   * Test generating a module with form.
   */
  public function testBasicFormGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'forms' => [
        0 => [
          'plain_class_name' => 'MyForm',
          'injected_services' => [],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "src/Form/MyForm.php",
    ], $files);

    $form_file = $files["src/Form/MyForm.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $form_file);
    $php_tester->assertDrupalCodingStandards([
      // Excluded because of the buildForm() commented-out code.
      'Drupal.Commenting.InlineComment.SpacingAfter',
      'Drupal.Commenting.InlineComment.InvalidEndChar',
    ]);
    $php_tester->assertHasClass('Drupal\test_module\Form\MyForm');
    $php_tester->assertClassHasParent('Drupal\Core\Form\FormBase');

    $method_tester = $php_tester->getMethodTester('getFormId');
    $method_tester->assertMethodDocblockHasInheritdoc();
    $method_tester->assertReturnsString('test_module_my_form');

    $form_builder_tester = $php_tester->getFormBuilderTester('buildForm');
    $form_builder_tester->assertHasLine('// $form = parent::buildForm($form, $form_state);');
    $form_builder_tester->assertElementCount(1);
    $form_builder_tester->assertAllElementsHaveDefaultValue();

    $method_tester = $php_tester->getMethodTester('validateForm');
    $method_tester->assertMethodDocblockHasInheritdoc();

    $method_tester = $php_tester->getMethodTester('submitForm');
    $method_tester->assertMethodDocblockHasInheritdoc();
  }

  /**
   * Test generating a form with custom form elements.
   */
  public function testFormGenerationWithCustomElements() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'forms' => [
        0 => [
          'plain_class_name' => 'MyForm',
          'form_elements' => [
            0 => [
              'form_key' => 'my_element',
              'element_type' => 'textfield',
            ],
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "src/Form/MyForm.php",
    ], $files);

    $form_file = $files["src/Form/MyForm.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $form_file);
    $php_tester->assertDrupalCodingStandards([
      // Excluded because of the buildForm() commented-out code.
      'Drupal.Commenting.InlineComment.SpacingAfter',
      'Drupal.Commenting.InlineComment.InvalidEndChar',
    ]);
    $php_tester->assertHasClass('Drupal\test_module\Form\MyForm');
    $php_tester->assertClassHasParent('Drupal\Core\Form\FormBase');

    $method_tester = $php_tester->getMethodTester('getFormId');
    $method_tester->assertMethodDocblockHasInheritdoc();
    $method_tester->assertReturnsString('test_module_my_form');

    // TODO: Can't use FormBuilderTester because the assertions in its
    // constructor will fail. See
    // https://github.com/drupal-code-builder/drupal-code-builder/issues/323.
    $form_builder_tester = $php_tester->getFormBuilderTester('buildForm', lenient_for_descriptions: TRUE);
    $form_builder_tester->assertHasLine('// $form = parent::buildForm($form, $form_state);');
  }

  /**
   * Test Form component with injected services.
   *
   * @group di
   */
  function testFormGenerationWithServices() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'forms' => [
        0 => [
          'plain_class_name' => 'MyForm',
          'injected_services' => [
            'current_user',
            'entity_type.manager',
            'storage:node',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "src/Form/MyForm.php",
    ], $files);

    $form_file = $files["src/Form/MyForm.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $form_file);
    $php_tester->assertDrupalCodingStandards([
      // Excluded because of the buildForm() commented-out code.
      'Drupal.Commenting.InlineComment.SpacingAfter',
      'Drupal.Commenting.InlineComment.InvalidEndChar',
    ]);
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
      [
        'typehint' => 'Drupal\Core\Entity\EntityStorageInterface',
        'service_name' => 'node',
        'property_name' => 'nodeStorage',
        'parameter_name' => 'node_storage',
        'extraction_call' => 'getStorage',
      ],
    ]);
  }

  /**
   * Data provider for testExistingFormAdoption().
   */
  public static function dataAdoptionMerge() {
    return [
      'no-merge' => [FALSE],
      'merge' => [TRUE],
    ];
  }

  /**
   * Tests adoption of existing form.
   *
   * @param bool $merge
   *   Whether a generated component exists to have the adopted component merged
   *   with.
   *
   * @group adopt
   *
   * @dataProvider dataAdoptionMerge
   */
  public function testExistingFormAdoption(bool $merge) {
    // First pass: generate the files we'll mock as existing.
    $module_name = 'existing';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'module_package' => 'Test Package',
      'readme' => FALSE,
      'forms' => [
        0 => [
          'plain_class_name' => 'AlphaForm',
          'injected_services' => [
            'current_user',
            'entity_type.manager',
          ],
        ],
      ],
    ];

    $existing_files = $this->generateModuleFiles($module_data);
    $extension = $this->getMockedExtension('module');
    $extension->setFile('src/Form/AlphaForm.php', $existing_files['src/Form/AlphaForm.php']);

    // Now generate the module again, passing in the mocked existing code.
    if ($merge) {
      unset($module_data['forms'][0]['injected_services']);
    }
    else {
      // If we're not testing merging, remove the form entirely.
      unset($module_data['forms']);
    }

    $component_data = $this->getRootComponentBlankData('module');
    $component_data->set($module_data);

    $task_handler_adopt = \DrupalCodeBuilder\Factory::getTask('Adopt');
    $items = $task_handler_adopt->listAdoptableComponents($component_data, $extension);
    $this->assertArrayHasKey('module:forms', $items);
    $this->assertArrayHasKey('src/Form/AlphaForm.php', $items['module:forms']);

    $task_handler_adopt->adoptComponent($component_data, $extension, 'module:forms', 'src/Form/AlphaForm.php');

    // Don't pass in the existing extension, to check the adopted form is
    // getting generated from scratch.
    $files = $this->generateComponentFilesFromData($component_data);
    $this->assertFiles([
      "$module_name.info.yml",
      "src/Form/AlphaForm.php",
    ], $files);

    $form_file = $files["src/Form/AlphaForm.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $form_file);
    $php_tester->assertDrupalCodingStandards([
      // Excluded because of the buildForm() commented-out code.
      'Drupal.Commenting.InlineComment.SpacingAfter',
      'Drupal.Commenting.InlineComment.InvalidEndChar',
    ]);
    $php_tester->assertHasClass('Drupal\existing\Form\AlphaForm');
    $php_tester->assertClassHasParent('Drupal\Core\Form\FormBase');
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
