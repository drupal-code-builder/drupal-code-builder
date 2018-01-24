<?php

namespace DrupalCodeBuilder\Test\Unit;

use \DrupalCodeBuilder\Exception\InvalidInputException;

/**
 * Tests the Plugins generator class.
 */
class ComponentPlugins8Test extends TestBaseComponentGeneration {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test Plugins component.
   */
  function testPluginsGeneration() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'plugins' => array(
        0 => [
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
        ]
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Plugin/Block/Alpha.php", $files, "The files list has a plugin file.");

    // Check the plugin file.
    $plugin_file = $files["src/Plugin/Block/Alpha.php"];
    $this->assertWellFormedPHP($plugin_file);
    $this->assertDrupalCodingStandards($plugin_file);

    $this->parseCode($plugin_file);
    $this->assertHasClass('Drupal\test_module\Plugin\Block\Alpha');
    $this->assertClassHasParent('Drupal\Core\Block\BlockBase');

    $this->assertNoTrailingWhitespace($plugin_file, "The plugin class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($plugin_file);

    $expected_annotation_properties = [
      'id' => 'test_module_alpha',
      // A value of NULL here means we don't test the value, only the key.
      'admin_label' => NULL,
      'category' => NULL,
    ];
    $this->assertClassAnnotation('Block', $expected_annotation_properties, $plugin_file, "The plugin class has the correct annotation.");

    // Interface methods.
    $this->assertMethod('blockForm', $plugin_file);
    $this->assertMethod('blockValidate  ', $plugin_file);
    $this->assertMethod('blockForm', $plugin_file);
  }

  /**
   * Test Plugins component using the plugin folder name.
   */
  function testPluginsGenerationFromPluginFolder() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'plugins' => array(
        0 => [
          // Specify the folder name rather than the plugin ID.
          'plugin_type' => 'Field/FieldFormatter',
          'plugin_name' => 'alpha',
        ],
        1 => [
          // Specify the namespace slice rather than the plugin ID.
          'plugin_type' => 'Field\FieldFormatter',
          'plugin_name' => 'beta',
        ],
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(3, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Plugin/Field/FieldFormatter/Alpha.php", $files, "The files list has a plugin file.");
    $this->assertArrayHasKey("src/Plugin/Field/FieldFormatter/Beta.php", $files, "The files list has a plugin file.");
  }

  /**
   * Test Plugins component with an invalid plugin type.
   */
  function testPluginsGenerationBadPluginType() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'plugins' => array(
        0 => [
          'plugin_type' => 'made_up',
          'plugin_name' => 'alpha',
        ]
      ),
      'readme' => FALSE,
    );

    $this->expectException(InvalidInputException::class);

    $files = $this->generateModuleFiles($module_data);
  }

  /**
   * Test Plugins component with injected services.
   */
  function testPluginsGenerationWithServices() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'plugins' => array(
        0 => [
          'plugin_type' => 'block',
          'plugin_name' => 'alpha',
          'injected_services' => [
            'current_user',
          ],
        ],
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Plugin/Block/Alpha.php", $files, "The files list has a plugin file.");

    // Check the plugin file.
    $plugin_file = $files["src/Plugin/Block/Alpha.php"];
    $this->assertWellFormedPHP($plugin_file);
    $this->assertDrupalCodingStandards($plugin_file);
    $this->assertNoTrailingWhitespace($plugin_file, "The plugin class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($plugin_file);

    $this->parseCode($plugin_file);
    $this->assertHasClass('Drupal\test_module\Plugin\Block\Alpha');
    $this->assertClassHasParent('Drupal\Core\Block\BlockBase');

    // Check service injection.
    $this->assertClassHasInterfaces([
      'Drupal\Core\Plugin\ContainerFactoryPluginInterface',
    ]);
    $this->assertInjectedServicesWithFactory([
      [
        'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
        'service_name' => 'current_user',
        'property_name' => 'currentUser',
        'parameter_name' => 'current_user',
      ],
    ]);
    $this->assertConstructorBaseParameters([
      'configuration' => 'array',
      'plugin_id' => NULL,
      'plugin_definition' => NULL,
    ]);
    // Check the construct() method calls the parent.
    // (Not yet covered by PHP Parser assertions.)
    $this->assertFunctionCode($plugin_file, '__construct', 'parent::__construct($configuration, $plugin_id, $plugin_definition);');

    $expected_annotation_properties = [
      'id' => 'test_module_alpha',
      // A value of NULL here means we don't test the value, only the key.
      'admin_label' => NULL,
      'category' => NULL,
    ];
    $this->assertClassAnnotation('Block', $expected_annotation_properties, $plugin_file, "The plugin class has the correct annotation.");
  }

  /**
   * Test Plugins component with a plugin base class with an existing create().
   */
  function testPluginsGenerationWithExistingCreate() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'plugins' => array(
        0 => [
          'plugin_type' => 'image.effect',
          'plugin_name' => 'alpha',
          'injected_services' => [
            'current_user',
          ],
        ],
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Plugin/ImageEffect/Alpha.php", $files, "The files list has a plugin file.");

    // Check the plugin file.
    $plugin_file = $files["src/Plugin/ImageEffect/Alpha.php"];
    $this->assertWellFormedPHP($plugin_file);
    $this->assertDrupalCodingStandards($plugin_file);
    $this->assertNoTrailingWhitespace($plugin_file, "The plugin class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($plugin_file);

    $this->parseCode($plugin_file);
    $this->assertHasClass('Drupal\test_module\Plugin\ImageEffect\Alpha');
    $this->assertClassHasParent('Drupal\image\ImageEffectBase');

    // Check service injection.
    $this->assertClassHasInterfaces([
      'Drupal\Core\Plugin\ContainerFactoryPluginInterface',
    ]);
    $this->assertInjectedServicesWithFactory([
      [
        'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
        'service_name' => 'current_user',
        'property_name' => 'currentUser',
        'parameter_name' => 'current_user',
      ],
    ]);
    $this->assertConstructorBaseParameters([
      'configuration' => 'array',
      'plugin_id' => NULL,
      'plugin_definition' => NULL,
      'logger' => 'Psr\Log\LoggerInterface',
      /*
      // TODO: figure out how to assert this.
      [
        'typehint' => 'Psr\Log\LoggerInterface',
        'service_name' => 'logger.factory',
        'property_name' => 'logger',
        'parameter_name' => 'logger',
      ],
      */
    ]);
    // Check the construct() method calls the parent.
    // (Not yet covered by PHP Parser assertions.)
    $this->assertFunctionCode($plugin_file, '__construct', 'parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);');
    // Check the create() method's return statement has an argument for the
    // base class's service.
    // (Not yet covered by PHP Parser assertions.)
    $this->assertFunctionCode($plugin_file, 'create', '$container->get(\'logger.factory\')->get(\'image\'),');
  }

  /**
   * Tests a plugin base class with nonstandard fixed constructor parameters.
   */
  function testPluginsGenerationWithNonstandardFixedParameters() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'plugins' => array(
        0 => [
          'plugin_type' => 'field.formatter',
          'plugin_name' => 'alpha',
          'injected_services' => [
            'current_user',
          ],
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    // Check the plugin file.
    $plugin_file = $files["src/Plugin/Field/FieldFormatter/Alpha.php"];
    $this->assertWellFormedPHP($plugin_file);
    $this->assertDrupalCodingStandards($plugin_file);
    $this->assertNoTrailingWhitespace($plugin_file, "The plugin class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($plugin_file);

    $this->parseCode($plugin_file);
    $this->assertHasClass('Drupal\test_module\Plugin\Field\FieldFormatter\Alpha');
    $this->assertClassHasParent('Drupal\Core\Field\FormatterBase');

    // Check service injection.
    $this->assertClassHasInterfaces([
      'Drupal\Core\Plugin\ContainerFactoryPluginInterface',
    ]);

    $this->assertMethodHasParameters([
      'container' => 'Symfony\Component\DependencyInjection\ContainerInterface',
      'configuration' => 'array',
      'plugin_id' => NULL,
      'plugin_definition' => NULL,
    ], 'create');

    $this->assertConstructorBaseParameters([
      'plugin_id' => NULL,
      'plugin_definition' => NULL,
      'field_definition' => 'Drupal\Core\Field\FieldDefinitionInterface',
      'settings' => 'array',
      'label' => NULL,
      'view_mode' => NULL,
      'third_party_settings' => 'array',
    ]);

    $this->assertInjectedServicesWithFactory([
      [
        'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
        'service_name' => 'current_user',
        'property_name' => 'currentUser',
        'parameter_name' => 'current_user',
      ],
    ]);
  }

}
