<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the Plugin Type generator class.
 */
class ComponentPluginType8Test extends TestBaseComponentGeneration {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test Plugin Type component.
   */
  function testPluginTypeGeneration() {
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

    // Check the plugin manager file.
    $plugin_manager_file = $files["src/CatFeederManager.php"];
    $this->assertWellFormedPHP($plugin_manager_file);
    $this->assertDrupalCodingStandards($plugin_manager_file);
    $this->assertNoTrailingWhitespace($plugin_manager_file, "The plugin service class file contains no trailing whitespace.");
    $this->assertClassFileFormatting($plugin_manager_file);

    // Check the annotation class file.
    $annotation_file = $files["src/Annotation/CatFeeder.php"];
    $this->assertWellFormedPHP($annotation_file);
    $this->assertDrupalCodingStandards($annotation_file);
    $this->parseCode($annotation_file);
    $this->assertHasClass('Drupal\test_module\Annotation\CatFeeder');
    $this->assertClassHasParent('Drupal\Component\Annotation\Plugin');
    $this->assertClassHasPublicProperty('id', 'string');
    $this->assertClassHasPublicProperty('label', 'Drupal\Core\Annotation\Translation');

    // Check the plugin base class file.
    $plugin_base_file = $files["src/Plugin/CatFeeder/CatFeederBase.php"];
    $this->assertWellFormedPHP($plugin_base_file);
    $this->assertDrupalCodingStandards($plugin_base_file);
    $this->assertNoTrailingWhitespace($plugin_base_file);
    // Doesn't work with interface and so on yet.
    //$this->assertClassFileFormatting($plugin_base_file);

    // Check the plugin interface file.
    $plugin_interface_file = $files["src/Plugin/CatFeeder/CatFeederInterface.php"];
    $this->assertWellFormedPHP($plugin_interface_file);
    $this->assertDrupalCodingStandards($plugin_interface_file);
    $this->assertNoTrailingWhitespace($plugin_interface_file);

    $this->parseCode($plugin_interface_file);
    $this->assertHasInterface('Drupal\test_module\Plugin\CatFeeder\CatFeederInterface');

    // TODO! test further file contents!

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
