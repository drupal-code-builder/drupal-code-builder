<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the Plugins generator class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/ComponentPluginType8Test.php
 * @endcode
 */
class ComponentPluginType8Test extends TestBase {

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
      'plugin_types' => array(
        0 => [
          'plugin_type' => 'cat_feeder',
        ]
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->assertArrayHasKey('src/CatFeederManager.php', $files, "The plugin manager class file is generated.");
    $this->assertArrayHasKey('src/Annotation/CatFeeder.php', $files, "The annotation class file is generated.");
    $this->assertArrayHasKey('src/Plugin/CatFeeder/CatFeederBase.php', $files, "The plugin base class file is generated.");
    $this->assertArrayHasKey('src/Plugin/CatFeeder/CatFeederInterface.php', $files, "The plugin interface file is generated.");
    $this->assertArrayHasKey('test_module.services.yml', $files, "The services file is generated.");
    $this->assertArrayHasKey('test_module.plugin_type.yml', $files, "The plugin type definition file is generated.");

    // TODO! test file contents!

    /*
    $file_names = array_keys($files);
    dump($file_names);

    dump($files['src/CatFeederManager.php']);
    dump($files['src/Annotation/CatFeeder.php']);
    dump($files['src/Plugin/CatFeeder/CatFeederBase.php']);
    dump($files['src/Plugin/CatFeeder/CatFeederInterface.php']);
    dump($files['test_module.services.yml']);
    dump($files['test_module.plugin_type.yml']);
    */
  }

}
