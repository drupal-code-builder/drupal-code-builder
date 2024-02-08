<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;
use MutableTypedData\Data\DataItem;

/**
 * Tests basic profile generation.
 */
class ComponentProfile10Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 10;

  /**
   * Tests a UI can access all of the necessary methods on component data.
   */
  public function testUiAccess() {
    $component_data = $this->getRootComponentBlankData('profile');

    $this->simulateUiWalk($component_data);

    $this->assertTrue(TRUE, 'We made it without crashing!');
  }

  /**
   * Helper for testUi().
   *
   * Recursively accesses label, descriptions, options on data items, creating
   * them as it goes to cover the whole data structure.
   *
   * TODO: doesn't handle mutable!
   */
  protected function simulateUiWalk(DataItem $data_item) {
    // Get the label and description.
    // If these are not properly defined, MTD will throw exceptions.
    $data_item->getLabel();
    $data_item->getDescription();

    if ($data_item->hasOptions()) {
      $options = $data_item->getOptions();
      foreach ($options as $value => $option) {
        // Get the label and description for the option.
        // If these are not properly defined, MTD will throw exceptions.
        $option->getLabel();
        $option->getDescription();
      }
    }

    // Recurse.
    foreach ($data_item as $property => $property_data_item) {
      // Ensure that data is created for complex properties and a single delta.
      $property_data_item->access();

      if ($property_data_item->isMultiple()) {
        $property_data_item->createItem();
      }

      $this->simulateUiWalk($property_data_item);
    }
  }

  /**
   * Tests getting module configuration data.
   *
   * @group config
   */
  public function testConfiguration() {
    $config_data = \DrupalCodeBuilder\Factory::getTask('Configuration')->getConfigurationData('profile');
    $properties = $config_data->getProperties();

    $this->assertEmpty($properties);
  }

  /**
   * Test requesting a profile with no options produces basic files.
   */
  function testNoOptions() {
    // Create a profile.
    $profile_name = 'myprofile';
    $profile_data = [
      'base' => 'profile',
      'root_name' => $profile_name,
      'readable_name' => 'Test profile',
      'short_description' => 'Test profile description',
      // 'readme' => FALSE,
    ];

    // Can't call generateModuleFiles() as that uses module data.
    $component_data = $this->getRootComponentBlankData('profile');
    $component_data->set($profile_data);
    $files = $this->generateComponentFilesFromData($component_data);
    $this->assertFiles([
      'myprofile.info.yml',
      'myprofile.install',
      'myprofile.profile',
    ], $files,);

    // Check the .info file.
    $info_file = $files['myprofile.info.yml'];
    $yaml_tester = new YamlTester($info_file);

    $yaml_tester->assertPropertyHasValue('name', 'Test profile');
    $yaml_tester->assertPropertyHasValue('type', 'profile');
    $yaml_tester->assertPropertyHasValue('description', $profile_data['short_description'], "The info file declares the profile description.");
    $yaml_tester->assertPropertyHasValue('core_version_requirement', '^8 || ^9 || ^10', "The info file declares the core version.");
  }

  /**
   * Tests info file options.
   */
  function testInfoOptions() {
    // Create a profile.
    $profile_name = 'myprofile';
    $profile_data = [
      'base' => 'profile',
      'root_name' => $profile_name,
      'readable_name' => 'Test profile',
      'short_description' => 'Test profile description',
      'install' => [
        'jsonapi',
      ],
      'dependencies' => [
        'node',
        'block',
      ],
      // 'readme' => FALSE,
    ];

    // Can't call generateModuleFiles() as that uses module data.
    $component_data = $this->getRootComponentBlankData('profile');
    $component_data->set($profile_data);
    $files = $this->generateComponentFilesFromData($component_data);
    $this->assertFiles([
      'myprofile.info.yml',
      'myprofile.install',
      'myprofile.profile',
    ], $files,);

    // Check the .info file.
    $info_file = $files['myprofile.info.yml'];
    $yaml_tester = new YamlTester($info_file);

    $yaml_tester->assertPropertyHasValue('name', 'Test profile');
    $yaml_tester->assertPropertyHasValue('install', ['jsonapi']);
    $yaml_tester->assertPropertyHasValue('dependencies', ['node', 'block']);
  }

}
