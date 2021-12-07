<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Exception\MergeDataLossException;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use DrupalCodeBuilder\Test\Fixtures\Generator\GenericGenerator;

/**
 * Tests merging components.
 *
 * TODO: change the assert() in mergeComponentData() to an exception so we
 * can test it here?
 */
class UnitMergeComponentDataTest extends TestCase {
  use ProphecyTrait;

  public function testMergeSingleSimpleData() {
    $definition = PropertyDefinition::create('complex')
      ->setName('root')
      ->setProperties([
        'simple' => PropertyDefinition::create('string'),
      ]);

    GenericGenerator::setPropertyDefinition($definition);

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $data->simple->set('value');
    $component = new GenericGenerator($data);

    $other_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $other_data->simple->set('value');

    $component->mergeComponentData($other_data);

    $this->assertEquals('value', $component->component_data->simple->value);
  }

  public function testMergeMultipleSimpleData() {
    $definition = PropertyDefinition::create('complex')
      ->setName('root')
      ->setProperties([
        'simple' => PropertyDefinition::create('string')
          ->setMultiple(TRUE)
      ]);

    GenericGenerator::setPropertyDefinition($definition);

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $data->simple->set('value 1');
    $component = new GenericGenerator($data);

    $other_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $other_data->simple->set('value 2');

    $component->mergeComponentData($other_data);

    $this->assertEquals(['value 1', 'value 2'], $component->component_data->simple->export());
  }

  public function testMergeSingleComplexData() {
    $definition = PropertyDefinition::create('complex')
      ->setName('root')
      ->setProperties([
        'complex' => PropertyDefinition::create('complex')
          ->setProperties([
            'alpha' => PropertyDefinition::create('string'),
            'beta' => PropertyDefinition::create('string'),
          ]),
      ]);

    GenericGenerator::setPropertyDefinition($definition);

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $data->complex->alpha->set('value');
    $component = new GenericGenerator($data);

    $other_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $other_data->complex->alpha->set('value');

    $component->mergeComponentData($other_data);

    $this->assertEquals('value', $component->component_data->complex->alpha->value);

    $other_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $other_data->complex->alpha->set('value 2');
    $other_data->complex->beta->set('value 2');

    $this->expectException(MergeDataLossException::class);
    $component->mergeComponentData($other_data);
  }

  public function testMergeMultipleComplexData() {
    $definition = PropertyDefinition::create('complex')
      ->setName('root')
      ->setProperties([
        'complex' => PropertyDefinition::create('complex')
          ->setMultiple(TRUE)
          ->setProperties([
            'alpha' => PropertyDefinition::create('string'),
            'beta' => PropertyDefinition::create('string'),
          ]),
      ]);

    GenericGenerator::setPropertyDefinition($definition);

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $data->complex[] = [
      'alpha' => 'value 1',
    ];
    $component = new GenericGenerator($data);

    $other_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $data->complex[] = [
      'alpha' => 'value 2',
      'beta' => 'value 2',
    ];

    $component->mergeComponentData($other_data);

    $this->assertEquals([
      'complex' => [
        0 => [
          'alpha' => 'value 1',
        ],
        1 => [
          'alpha' => 'value 2',
          'beta' => 'value 2',
        ],
      ],
    ], $component->component_data->export());
  }

}
