<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Generator\Collection\ComponentCollection;
use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Generator\RootComponent;
use Prophecy\PhpUnit\ProphecyTrait;
use MutableTypedData\Test\VarDumperSetupTrait;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;

/**
 * Unit tests TODO for the ComponentCollection class.
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



  public function testFoo() {
    $class_handler = new \DrupalCodeBuilder\Test\Fixtures\Task\TestComponentClassHandler('Generator\Mogrifier');
    $this->container->set('Generate\ComponentClassHandler', $class_handler);

    // $this->container->set(
    //   'Generate|mogrifier',
    //   \DI\factory([\DrupalCodeBuilder\DependencyInjection\ContainerBuilder::class, 'createGenerator'])
    //     ->parameter('root_component_type', 'mogrifier')
    // );


    // $task_handler_generate = $this->container->get('Generate', $type);
    // $component_data = $task_handler_generate->getRootComponentData();
    //

    // We have to do the work of Generate::getRootComponentData() because non-
    // standard root component.
    // TODO! should accept lowercase! we need to put that through something elkse first!
    $class = $class_handler->getGeneratorClass('Mogrifier');
    $data = DrupalCodeBuilderDataItemFactory::createFromProvider($class);

  }

}
