<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Data\StringData;
use MutableTypedData\Data\ArrayData;
use MutableTypedData\Data\ComplexData;
use MutableTypedData\Data\MutableData;
use MutableTypedData\DataItemFactory;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\OptionDefinition;
use MutableTypedData\Definition\PropertyDefinition;
use MutableTypedData\Definition\VariantDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Test\VarDumperSetupTrait;

/**
 * Unit tests for the DataItemArrayAccessTrait trait.
 */
class UnitDataItemArrayAccessTest extends TestCase {

  use VarDumperSetupTrait;

  protected function setUp() {
    $this->setUpVarDumper();
  }

  /**
   * Tests accessing complex data values as arrays.
   */
  public function testDataItem() {
    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition(
      PropertyDefinition::create('complex')
        ->setMachineName('root')
        ->setProperties([
          'plain' => PropertyDefinition::create('string'),
          'default' => PropertyDefinition::create('string')
            ->setDefault(
              DefaultDefinition::create()
                ->setLiteral("foo")
            ),
          'lazy_default' => PropertyDefinition::create('string')
            ->setDefault(
              DefaultDefinition::create()
                ->setLiteral('lazy')
                ->setLazy(TRUE)
            ),
        ])

    );

    $data->plain->set('value');
    $this->assertEquals('value', $data['plain']);

    $this->assertEquals('foo', $data['default']);

    $this->assertEquals('lazy', $data['lazy_default']);
  }

}
