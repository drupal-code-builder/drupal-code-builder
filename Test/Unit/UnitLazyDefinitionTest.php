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
