<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Generator\Collection\ComponentCollection;
use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Generator\RootComponent;
use Prophecy\PhpUnit\ProphecyTrait;
use MutableTypedData\Test\VarDumperSetupTrait;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Data\DataItem;

/**
 * Tests the assembly of a data definition from multiple Generator classes.
 *
 * Uses the 'Mogrifier' set of fixture generators.
 */
class UnitPropertyAssemblyTest extends TestCase {

  use ProphecyTrait;
  use VarDumperSetupTrait;

  /**
   * The service container.
   */
  protected $container;

  protected function setUp(): void {
    $this->setUpVarDumper();

    $this->setupDrupalCodeBuilder(10);
    $this->container = \DrupalCodeBuilder\Factory::getContainer();
  }

  /**
   * Perform the factory setup, spoofing in the given core major version.
   *
   * @param $version
   *  A core major version number,
   */
  protected function setupDrupalCodeBuilder($version) {
    $environment = new \DrupalCodeBuilder\Environment\TestsSampleLocation;

    $version_helper = new \DrupalCodeBuilder\Environment\VersionHelperTestsPHPUnit;
    $version_helper->setFakeCoreMajorVersion($version);

    \DrupalCodeBuilder\Factory::setEnvironment($environment)->setCoreVersionHelper($version_helper);
  }

  /**
   * Tests data definition assembly.
   */
  public function testComponentPropertyAssembly() {
    $class_handler = new \DrupalCodeBuilder\Test\Fixtures\Task\TestComponentClassHandler('Generator\Mogrifier');
    $this->container->set('Generate\ComponentClassHandler', $class_handler);

    // We have to do the work of Generate::getRootComponentData() because we're
    // using a non- standard root component -- there isn't a flavour of the
    // Generate task in the DI container for our root component.
    // TODO! should accept lowercase! we need to put that through something else
    // first!
    $class = $class_handler->getGeneratorClass('Mogrifier');
    $data = DrupalCodeBuilderDataItemFactory::createFromProvider($class);
    // dump($data->getDefinition());
    //

    $this->simulateUiWalk($data);


    // Test boolean property works.
    $data->boolean_generator_property = TRUE;

    // Test complex property works.
    $data->complex_generator_property[0]->string_property = 'cake';

    // Test recursive generator properties work.
    $data->complex_generator_property[0]->recursive->string_property = 'cake';

    // Test mutable property works.
    $data->mutable_generator_property[0]->type = 'alpha';
    $this->assertArrayHasKey('alpha_property', $data->mutable_generator_property[0]->getProperties());
  }

  /**
   * Helper recursively walking the data.
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

}
