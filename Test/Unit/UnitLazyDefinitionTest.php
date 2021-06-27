<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use MutableTypedData\Definition\DataDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use DrupalCodeBuilder\Definition\LazyGeneratorDefinition;
use MutableTypedData\Exception\InvalidDefinitionException;
use MutableTypedData\Test\VarDumperSetupTrait;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit tests for the LazyGeneratorDefinition class.
 */
class UnitLazyDefinitionTest extends TestCase {
  use ProphecyTrait;
  use VarDumperSetupTrait;

  protected function setUp(): void {
    $environment = $this->prophesize(\DrupalCodeBuilder\Environment\EnvironmentInterface::class);
    \DrupalCodeBuilder\Factory::setEnvironment($environment->reveal());

    $this->container = \DrupalCodeBuilder\Factory::getContainer();

    $this->setUpVarDumper();
  }

  /**
   * Tests accessing complex data values as arrays.
   */
  public function testLazyGeneratorDefinition() {
    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition(
      DataDefinition::create('complex')
        ->setName('root')
        ->setProperties([
          'plain' => DataDefinition::create('string'),
          // This isn't defined yet in the container until later, but as it's
          // lazy, won't be accessed yet.
          'lazy' => LazyGeneratorDefinition::createFromGeneratorType('LazyType'),
          // This won't defined in the container at all, and so we expect an
          // exception when we try to access it.
          'lazy_bad' => LazyGeneratorDefinition::createFromGeneratorType('LazyTypeDoesNotExist'),
        ])
    );

    // Now set up the LazyType generator type.
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);
    $class_handler->getGeneratorClass('LazyType')
      ->willReturn('DrupalCodeBuilder\Fixture\LazyType');

    $class_handler->getGeneratorClass('LazyTypeDoesNotExist')
      ->willThrow(InvalidDefinitionException::class);

    $container = \DrupalCodeBuilder\Factory::getContainer();
    $container->set('Generate\ComponentClassHandler', $class_handler->reveal());

    $data->plain->set('value');
    $this->assertEquals('value', $data['plain']);

    $data->lazy->lazy_one->set('value');
    $data->lazy->lazy_two->set(TRUE);

    $this->expectException(InvalidDefinitionException::class);

    $data->lazy_bad->set('crash');
  }

  /**
   * Test a root component can unset properties from parent that do not apply.
   *
   * In particular, a root component's parent might set properties that need
   * options that don't make sense in the child's context. E.g. Module8 has
   * services and plugins, Module7 doesn't. If those properties' options get
   * loaded, the report system will try to load analysis data files that don't
   * exist.
   */
  public function testParentRootComponentPropertyRemoval() {
    $class_handler = new \DrupalCodeBuilder\Test\Fixtures\Task\TestComponentClassHandler;
    $this->container->set('Generate\ComponentClassHandler', $class_handler);

    $generator_class = $class_handler->getGeneratorClass('RootGeneratorChild');

    /** @var \DrupalCodeBuilder\Definition\PropertyDefinition */
    $definition = $generator_class::getDefinition();
    $property_names = $definition->getPropertyNames();

    $this->assertContains('common', $property_names);
    $this->assertNotContains('only_base', $property_names);

    $data = DrupalCodeBuilderDataItemFactory::createFromProvider(\DrupalCodeBuilder\Test\Fixtures\Generator\RootGeneratorChild::class);
  }

  /**
   * Get a component collector with mocked dependencies.
   *
   * This uses the TestComponentClassHandler from fixtures, which in turns
   * returns a \DrupalCodeBuilder\Test\Fixtures\Generator\SimpleGenerator
   * for components.
   */
  protected function getComponentCollector(): ComponentCollector {
    // Set up the ComponentCollector's injected dependencies.
    $environment = $this->prophesize(\DrupalCodeBuilder\Environment\EnvironmentInterface::class);
    $class_handler = new \DrupalCodeBuilder\Test\Fixtures\Task\TestComponentClassHandler;
    $data_info_gatherer = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentDataInfoGatherer::class);

    // Create the helper, with dependencies passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler,
      $data_info_gatherer->reveal()
    );

    return $component_collector;
  }

}

namespace DrupalCodeBuilder\Fixture;

/**
 * Generator fixture, because can't mock statics.
 */
class LazyType {

  public static function setProperties(\DrupalCodeBuilder\Definition\PropertyDefinition $definition): void {
    $definition->setProperties([
      'lazy_one' => \MutableTypedData\Definition\DataDefinition::create('string'),
      'lazy_two' => \MutableTypedData\Definition\DataDefinition::create('boolean'),
    ]);
  }

}
