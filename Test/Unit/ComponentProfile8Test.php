<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use MutableTypedData\Data\DataItem;

/**
 * Tests basic profile generation.
 */
class ComponentProfile8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

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
   * Test requesting a module with no options produces basic files.
   */
  function testNoOptions() {
    // Create a profile.
    $profile_name = 'myprofile';
    $profile_data = [
      'base' => 'profile',
      'root_name' => $profile_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      // 'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($profile_data);
    $file_names = array_keys($files);

    $this->assertCount(1, $files, "One file is returned.");

    $this->assertArrayHasKey("$profile_name.info.yml", $files, "The files list has a .info.yml file.");
  }

}
