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
    $this->assertContains("$module_name.info.yml", $file_names, "The files list has a .info.yml file.");
    $this->assertContains("src/Plugin/Block/Alpha.php", $file_names, "The files list has a plugin file.");

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
        ]
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(2, $files, "Expected number of files is returned.");
    $this->assertContains("$module_name.info.yml", $file_names, "The files list has a .info.yml file.");
    $this->assertContains("src/Plugin/Field/FieldFormatter/Alpha.php", $file_names, "The files list has a plugin file.");
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
    $this->assertContains("$module_name.info.yml", $file_names, "The files list has a .info.yml file.");
    $this->assertContains("src/Plugin/Block/Alpha.php", $file_names, "The files list has a plugin file.");

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

    $expected_annotation_properties = [
      'id' => 'test_module_alpha',
      // A value of NULL here means we don't test the value, only the key.
      'admin_label' => NULL,
      'category' => NULL,
    ];
    $this->assertClassAnnotation('Block', $expected_annotation_properties, $plugin_file, "The plugin class has the correct annotation.");

    // Check the injected service.
    $this->assertClassProperty('currentUser', $plugin_file, "The plugin class has a property for the injected service.");

    $this->assertMethod('__construct', $plugin_file, "The plugin class has a constructor method.");
    $parameters = [
      'configuration',
      'plugin_id',
      'plugin_definition',
      'current_user',
    ];
    $this->assertFunctionHasParameters('__construct', $parameters, $plugin_file);
    $this->assertFunctionCode($plugin_file, '__construct', 'parent::__construct($configuration, $plugin_id, $plugin_definition);');
    $this->assertFunctionCode($plugin_file, '__construct', '$this->currentUser = $current_user;');

    $this->assertMethod('create', $plugin_file, "The plugin class has a create method.");
    $parameters = [
      'container',
      'configuration',
      'plugin_id',
      'plugin_definition',
    ];
    $this->assertFunctionHasParameters('create', $parameters, $plugin_file);
    $this->assertFunctionCode($plugin_file, 'create', '$container->get(\'current_user\')');
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
    $this->assertContains("$module_name.info.yml", $file_names, "The files list has a .info.yml file.");
    $this->assertContains("src/Plugin/ImageEffect/Alpha.php", $file_names, "The files list has a plugin file.");

    // Check the plugin file.
    $plugin_file = $files["src/Plugin/ImageEffect/Alpha.php"];
    $this->assertWellFormedPHP($plugin_file);
    $this->assertDrupalCodingStandards($plugin_file);
    $this->assertNoTrailingWhitespace($plugin_file, "The plugin class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($plugin_file);

    $this->assertClassImport(['Psr', 'Log', 'LoggerInterface'], $plugin_file);

    $this->assertMethod('__construct', $plugin_file, "The plugin class has a constructor method.");
    $parameters = [
      'configuration',
      'plugin_id',
      'plugin_definition',
      'logger',
      'current_user',
    ];
    $this->assertFunctionHasParameters('__construct', $parameters, $plugin_file);
    $this->assertFunctionCode($plugin_file, '__construct', 'parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);');
    $this->assertFunctionCode($plugin_file, '__construct', '$this->currentUser = $current_user;');

    $this->assertMethod('create', $plugin_file, "The plugin class has a create method.");
    $parameters = [
      'container',
      'configuration',
      'plugin_id',
      'plugin_definition',
    ];
    $this->assertFunctionHasParameters('create', $parameters, $plugin_file);
    $this->assertFunctionCode($plugin_file, 'create', '$container->get(\'logger.factory\')->get(\'image\'),');
    $this->assertFunctionCode($plugin_file, 'create', '$container->get(\'current_user\')');
  }

}
