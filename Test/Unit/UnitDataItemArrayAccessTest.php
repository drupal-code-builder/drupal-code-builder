<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\DataDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Test\VarDumperSetupTrait;

/**
 * Unit tests for the DataItemArrayAccessTrait trait.
 */
class UnitDataItemArrayAccessTest extends TestCase {

  use VarDumperSetupTrait;

  protected function setUp(): void {
    $this->setUpVarDumper();
  }

  /**
   * Tests accessing complex data values as arrays.
   */
  public function testDataItem() {
    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition(
      DataDefinition::create('complex')
        ->setName('root')
        ->setProperties([
          'plain' => DataDefinition::create('string'),
          'default' => DataDefinition::create('string')
            ->setDefault(
              DefaultDefinition::create()
                ->setLiteral("foo")
            ),
          'lazy_default' => DataDefinition::create('string')
            ->setDefault(
              DefaultDefinition::create()
                ->setLiteral('lazy')
            ),
        ])

    );

    $data->plain->set('value');
    $this->assertEquals('value', $data['plain']);

    $this->assertEquals('foo', $data['default']);

    $this->assertEquals('lazy', $data['lazy_default']);
  }

}
