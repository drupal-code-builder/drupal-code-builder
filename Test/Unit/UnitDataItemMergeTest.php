<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Exception\MergeDataLossException;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Test\VarDumperSetupTrait;

/**
 * Unit tests for data item merging.
 */
class UnitDataItemMergeTest extends TestCase {

  use VarDumperSetupTrait;

  protected function setUp(): void {
    $this->setUpVarDumper();
  }

  /**
   * Tests single-valued simple data.
   */
  public function testSingleSimpleData() {
    $definition = PropertyDefinition::create('string');

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $data->set('value');

    $other_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $other_data->set('value');

    $data->merge($other_data);
    $this->assertEquals('value', $data->value);

    $other_data->set('different');

    $this->expectException(MergeDataLossException::class);
    $result = $data->merge($other_data);
    $this->assertEquals(FALSE, $result);
  }

  /**
   * Tests multi-valued simple data.
   *
   * @dataProvider dataMultipleSimpleData
   */
  public function testMultipleSimpleData(array $original_values, array $other_values, ?array $end_values, ?bool $expected_result) {
    $definition = PropertyDefinition::create('string')
      ->setMultiple(TRUE);

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $data->set($original_values);

    $other_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $other_data->set($other_values);

    $result = $data->merge($other_data);
    $this->assertEquals($end_values, $data->export());
    $this->assertEquals($expected_result, $result);
  }

  /**
   * Data provider for testMultipleSimpleData().
   */
  public function dataMultipleSimpleData() {
    return [
      'nothing all round' => [
        [],
        [],
        [],
        FALSE,
      ],
      'nothing added' => [
        [
          'one',
        ],
        [],
        [
          'one',
        ],
        FALSE,
      ],
      'add to empty' => [
        [],
        [
          'one',
        ],
        [
          'one',
        ],
        TRUE,
      ],
      'identical' => [
        [
          'one',
        ],
        [
          'one',
        ],
        [
          'one',
        ],
        FALSE,
      ],
      'appended' => [
        [
          'one',
        ],
        [
          'two',
        ],
        [
          'one',
          'two',
        ],
        TRUE,
      ],
      'appended multiple' => [
        [
          'one',
          'two',
        ],
        [
          'two',
          'three',
        ],
        [
          'one',
          'two',
          'three',
        ],
        TRUE,
      ],
    ];
  }

  /**
   * Tests single-valued complex data.
   *
   * @dataProvider dataSingleComplexData
   */
  public function testSingleComplexData(array $original_values, array $other_values, ?array $end_values, ?bool $expected_result, bool $expect_exception) {
    $definition = PropertyDefinition::create('complex')
      ->setProperties([
        'alpha' => PropertyDefinition::create('string'),
        'beta' => PropertyDefinition::create('string'),
      ]);

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $data->set($original_values);

    $other_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $other_data->set($other_values);

    if ($expect_exception) {
      $this->expectException(MergeDataLossException::class);
    }
    $result = $data->merge($other_data);
    $this->assertEquals($end_values, $data->export());
    $this->assertEquals($expected_result, $result);
  }

  /**
   * Data provider for testSingleComplexData().
   */
  public function dataSingleComplexData() {
    return [
      'identical' => [
        [
          'alpha' => 'value',
        ],
        [
          'alpha' => 'value',
        ],
        [
          'alpha' => 'value',
        ],
        FALSE,
        FALSE,
      ],
      'dovetail' => [
        [
          'alpha' => 'value',
        ],
        [
          'beta' => 'value',
        ],
        [
          'alpha' => 'value',
          'beta' => 'value',
        ],
        TRUE,
        FALSE,
      ],
      'different' => [
        [
          'alpha' => 'value',
        ],
        [
          'alpha' => 'value 2',
        ],
        NULL,
        NULL,
        TRUE,
      ],
    ];
  }

  /**
   * Tests multi-valued complex data.
   *
   * @dataProvider dataMultipleComplexData
   */
  public function testMultipleComplexData(array $original_values, array $other_values, ?array $end_values, ?bool $expected_result) {
    $definition = PropertyDefinition::create('complex')
      ->setMultiple(TRUE)
      ->setProperties([
        'alpha' => PropertyDefinition::create('string'),
        'beta' => PropertyDefinition::create('string'),
      ]);

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $data->set($original_values);

    $other_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $other_data->set($other_values);

    $result = $data->merge($other_data);
    $this->assertEquals($end_values, $data->export());
    $this->assertEquals($expected_result, $result);
  }

  /**
   * Data provider for testMultipleComplexData().
   */
  public function dataMultipleComplexData() {
    return [
      'nothing all round' => [
        [],
        [],
        [],
        FALSE,
      ],
      'nothing added' => [
        [
          [
            'alpha' => 'value',
          ],
        ],
        [],
        [
          [
            'alpha' => 'value',
          ],
        ],
        FALSE,
      ],
      'add to empty' => [
        [],
        [
          [
            'alpha' => 'value',
          ],
        ],
        [
          [
            'alpha' => 'value',
          ],
        ],
        TRUE,
      ],
      'identical' => [
        [
          [
            'alpha' => 'value',
          ],
        ],
        [
          [
            'alpha' => 'value',
          ],
        ],
        [
          [
            'alpha' => 'value',
          ],
        ],
        FALSE,
      ],
      'appended' => [
        [
          [
            'alpha' => 'value',
          ],
        ],
        [
          [
            'alpha' => 'value 2',
          ],
        ],
        [
          [
            'alpha' => 'value',
          ],
          [
            'alpha' => 'value 2',
          ],
        ],
        TRUE,
      ],
      'appended multiple' => [
        [
          [
            'alpha' => 'value',
          ],
        ],
        [
          [
            'alpha' => 'value 2',
          ],
          [
            'beta' => 'value 3',
          ],
        ],
        [
          [
            'alpha' => 'value',
          ],
          [
            'alpha' => 'value 2',
          ],
          [
            'beta' => 'value 3',
          ],
        ],
        TRUE,
      ],
    ];
  }

  /**
   * Tests merging mapping data.
   *
   * We only cover single-valued as mapping data is rarely if ever multiple.
   *
   * @dataProvider dataMappingData
   */
  public function testMappingData(array $original_values, array $other_values, ?array $end_values, ?bool $expected_result, bool $expect_exception) {
    $definition = PropertyDefinition::create('mapping');

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $data->set($original_values);

    $other_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $other_data->set($other_values);

    if ($expect_exception) {
      $this->expectException(MergeDataLossException::class);
    }
    $result = $data->merge($other_data);
    $this->assertEquals($end_values, $data->export());
    $this->assertEquals($expected_result, $result);
  }

  /**
   * Data provider for testMappingData().
   */
  public function dataMappingData() {
    return [
      'nothing all round' => [
        [],
        [],
        [],
        FALSE,
        FALSE,
      ],
      'nothing added' => [
        ['one' => 'value'],
        [],
        ['one' => 'value'],
        FALSE,
        FALSE,
      ],
      'add to nothing' => [
        [],
        ['one' => 'value'],
        ['one' => 'value'],
        TRUE,
        FALSE,
      ],
      'merge numeric' => [
        [0 => 'zero'],
        [1 => 'one'],
        [
          0 => 'zero',
          1 => 'one',
        ],
        TRUE,
        FALSE,
      ],
      'merge associative' => [
        ['alpha' => 'value'],
        ['beta' => 'value'],
        [
          'alpha' => 'value',
          'beta' => 'value',
        ],
        TRUE,
        FALSE,
      ],
      'matching associative' => [
        ['alpha' => 'value'],
        ['alpha' => 'value'],
        ['alpha' => 'value'],
        FALSE,
        FALSE,
      ],
      'clashing associative' => [
        ['alpha' => 'value'],
        ['alpha' => 'different'],
        NULL,
        NULL,
        TRUE,
      ],
      'merge deep numeric' => [
        [
          'compound' => [
            0 => 'value',
          ],
        ],
        [
          'compound' => [
            0 => 'different',
          ],
        ],
        [
          'compound' => [
            0 => 'value',
            1 => 'different',
          ],
        ],
        TRUE,
        FALSE,
      ],
      'merge deep associative' => [
        [
          'compound' => [
            'alpha' => 'value',
          ]
        ],
        [
          'compound' => [
            'beta' => 'value',
          ]
        ],
        [
          'compound' => [
            'alpha' => 'value',
            'beta' => 'value',
          ],
        ],
        TRUE,
        FALSE,
      ],
    ];
  }

}
