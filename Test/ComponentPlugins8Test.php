<?php

/**
 * @file
 * Contains ComponentPlugins8Test.
 */

namespace DrupalCodeBuilder\Test;

/**
 * Tests the Plugins generator class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/ComponentPlugins8Test.php
 * @endcode
 */
class ComponentPlugins8Test extends TestBase {

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
    $this->assertNoTrailingWhitespace($plugin_file, "The plugin class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($plugin_file);

    $this->assertNamespace(['Drupal', $module_name, 'Plugin', 'Block'], $plugin_file, "The plugin class file contains contains the expected namespace.");

    $expected_annotation_properties = [
      'id' => 'test_module_alpha',
      // A value of NULL here means we don't test the value, only the key.
      'admin_label' => NULL,
      'category' => NULL,
    ];
    $this->assertClassAnnotation($plugin_file, 'Block', $expected_annotation_properties, "The plugin class has the correct annotation.");
    $this->assertClass('Alpha', $plugin_file, "The plugin class file contains contains the expected class.");
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
    $this->assertNoTrailingWhitespace($plugin_file, "The plugin class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($plugin_file);

    $this->assertNamespace(['Drupal', $module_name, 'Plugin', 'Block'], $plugin_file, "The plugin class file contains contains the expected namespace.");
    $this->assertClassImport(['Drupal', 'Core', 'Plugin', 'ContainerFactoryPluginInterface'], $plugin_file);
    $this->assertClassImport(['Symfony', 'Component', 'DependencyInjection', 'ContainerInterface'], $plugin_file);

    $expected_annotation_properties = [
      'id' => 'test_module_alpha',
      // A value of NULL here means we don't test the value, only the key.
      'admin_label' => NULL,
      'category' => NULL,
    ];
    $this->assertClassAnnotation($plugin_file, 'Block', $expected_annotation_properties, "The plugin class has the correct annotation.");

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

}
