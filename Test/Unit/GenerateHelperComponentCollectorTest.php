<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use DrupalCodeBuilder\Task\Generate\ComponentCollector;
use Prophecy\Argument;

/**
 * Unit test for the ComponentCollector Generate helper.
 */
class GenerateHelperComponentCollectorTest extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

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

    // Create the helper, with dependencies passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler
    );

    return $component_collector;
  }

  /**
   * Request with only the root generator, which itself has no requirements.
   */
  public function testSingleGeneratorNoRequirements() {
    $definition = GeneratorDefinition::createFromGeneratorType('my_root')
      ->setProperties([
        'one' => PropertyDefinition::create('string'),
        'two' => PropertyDefinition::create('string'),
      ]);
    $component_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);

    $component_data->set([
      'one' => 'foo',
      'two' => 'bar',
    ]);

    // Set up the ComponentCollector's injected dependencies.
    $environment = $this->prophesize(\DrupalCodeBuilder\Environment\EnvironmentInterface::class);
    $class_handler = new \DrupalCodeBuilder\Test\Fixtures\Task\TestComponentClassHandler;

    // Create the helper, with dependencies passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler
    );

    $collection = $component_collector->assembleComponentList($component_data);

    $component_paths = $collection->getComponentRequestPaths();

    $this->assertCount(1, $component_paths, "The expected number of components is returned.");
    $this->assertContains('root', $component_paths, "The component list has the root generator.");
  }

  /**
   * Data provider.
   */
  public function providerGeneratorChildNoRequests() {
    return [
      'string_only' => [
        'data_value' => [
          'one' => 'foo',
        ],
        'expected_paths' => [
          'root',
        ],
      ],
      'string_and_single_compound' => [
        'data_value' => [
          'one' => 'foo',
          'component_property_compound_single' => [
            'child_one' => 'bar',
          ],
        ],
        'expected_paths' => [
          'root',
          'root/component_property_compound_single',
        ],
      ],
      'string_and_multiple_compound_one_delta' => [
        'data_value' => [
          'one' => 'foo',
          'component_property_compound_single' => [
            'child_one' => 'bar',
          ],
          'component_property_compound_multiple' => [
            0 => [
              'child_one' => 'bar',
            ],
          ],
        ],
        'expected_paths' => [
          'root',
          'root/component_property_compound_single',
          'root/component_property_compound_multiple_0',
        ],
      ],
      'string_and_multiple_compound_several_deltas' => [
        'data_value' => [
          'one' => 'foo',
          'component_property_compound_single' => [
            'child_one' => 'bar',
          ],
          'component_property_compound_multiple' => [
            0 => [
              'child_one' => 'bar',
            ],
            1 => [
              'child_one' => 'bax',
            ],
          ],
        ],
        'expected_paths' => [
          'root',
          'root/component_property_compound_single',
          'root/component_property_compound_multiple_0',
          'root/component_property_compound_multiple_1',
        ],
      ],
      'string_and_multiple_compound_grandchild' => [
        'data_value' => [
          'one' => 'foo',
          'component_property_compound_single' => [
            'child_one' => 'bar',
          ],
          'component_property_compound_multiple' => [
            0 => [
              'child_one' => 'bar',
              'child_compound' => [
                'grandchild_one' => 'bax',
              ],
            ],
            1 => [
              'child_one' => 'bax',
            ],
          ],
        ],
        'expected_paths' => [
          'root',
          'root/component_property_compound_single',
          'root/component_property_compound_multiple_0',
          'root/component_property_compound_multiple_0/child_compound',
          'root/component_property_compound_multiple_1',
        ],
      ],
    ];
  }

  /**
   * Request with nested component properties.
   *
   * @dataProvider providerGeneratorChildNoRequests
   */
  public function testGeneratorChildNoRequests($data_value, $expected_paths) {
    $definition = GeneratorDefinition::createFromGeneratorType('my_root')
      ->setName('my_root')
      ->setProperties([
        'one' => PropertyDefinition::create('string'),
        'component_property_compound_single' => GeneratorDefinition::createFromGeneratorType('compound_a')
          ->setProperties([
            'child_one' => PropertyDefinition::create('string'),
            'child_two' => PropertyDefinition::create('string'),
          ]),
        'component_property_compound_multiple' => GeneratorDefinition::createFromGeneratorType('compound_b')
          ->setMultiple(TRUE)
          ->setProperties([
            'child_one' => PropertyDefinition::create('string'),
            'child_compound' => GeneratorDefinition::createFromGeneratorType('compound_b_child')
              ->setProperties([
                'grandchild_one' => PropertyDefinition::create('string'),
              ]),
          ]),
      ]);

    $component_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $component_data->set($data_value);

    // Mock the ComponentCollector's injected dependencies.
    $environment = $this->prophesize(\DrupalCodeBuilder\Environment\EnvironmentInterface::class);
    $class_handler = new \DrupalCodeBuilder\Test\Fixtures\Task\TestComponentClassHandler;

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler
    );

    $collection = $component_collector->assembleComponentList($component_data);

    $component_paths = $collection->getComponentRequestPaths();

    $this->assertEquals($expected_paths, array_values($component_paths));
  }

  /**
   * Tests a component property that's a multi-valued string.
   *
   * The idea here is that the UI specifies just a list; and then a component
   * is created for each value.
   */
  public function testMultipleStringGeneratorChildNoRequests() {
    $definition = GeneratorDefinition::createFromGeneratorType('my_root')
      ->setName('my_root')
      ->setProperties([
        'component_property_string_multiple' => GeneratorDefinition::createFromGeneratorType('compound_a', 'string')
          ->setMultiple(TRUE),
      ]);

    $component_data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);

    $data_value = [
      'component_property_string_multiple' => [
        'foo',
        'bar',
      ],
    ];
    $component_data->set($data_value);

    $component_collector = $this->getComponentCollector();
    $collection = $component_collector->assembleComponentList($component_data);

    $component_paths = $collection->getComponentRequestPaths();

    $expected_paths = [
      'root',
      'root/component_property_string_multiple_0',
      'root/component_property_string_multiple_1',
    ];

    $this->assertEquals($expected_paths, array_values($component_paths));
  }

  /**
   * Request with only the root generator, with a single-value preset.
   *
   * @group presets
   */
  public function testSingleGeneratorSinglePresetsNoRequirements() {
    $this->markTestSkipped();

    // The mocked root component's data info.
    $root_data_info = [
      // This property is assumed to exist by the collector.
      'root_name' => [
        'label' => 'Component machine name',
        'required' => TRUE,
      ],
      'preset_property' => [
        'label' => 'Label',
        'presets' => [
          'A' => [
            'label' => 'option_a',
            'data' => [
              'force' => [
                'forced_property' => [
                  'value' => 'forced_A',
                ],
              ],
              'suggest' => [
                'suggested_property_filled' => [
                  'value' => 'suggested_A',
                ],
                'suggested_property_empty' => [
                  'value' => 'suggested_A',
                ],
              ],
            ],
          ],
          'B' => [
            'label' => 'option_b',
            'data' => [
              'force' => [
                'forced_property' => [
                  'value' => 'forced_B',
                ],
              ],
              'suggest' => [
                'suggested_property_filled' => [
                  'value' => 'suggested_B',
                ],
                'suggested_property_empty' => [
                  'value' => 'suggested_B',
                ],
              ],
            ],
          ],
        ],
      ],
      'forced_property' => [
      ],
      'suggested_property_filled' => [
      ],
      'suggested_property_empty' => [
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);
    // The request data we pass in to the system.
    $root_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'preset_property' => 'A',
      // The user supplies a value for the suggested property.
      'suggested_property_filled' => 'user_filled',
    ];
    // Expected data for the root component.
    // This is $root_data once it's been processed.
    $root_component_construction_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'preset_property' => 'A',
      // The suggested value isn't used, because the user supplied a value.
      'suggested_property_filled' => 'user_filled',
      // The force value is used.
      'forced_property' => 'forced_A',
      // The suggested value is used, because the user didn't supply a value.
      'suggested_property_empty' => 'suggested_A',
      'component_type' => 'my_root',
    ];

    // Mock the ComponentCollector's injected dependencies.
    $environment = $this->prophesize('\DrupalCodeBuilder\Environment\EnvironmentInterface');
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);

    // Mock the root component generator, and methods on the dependencies which
    // return things relating to it.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getMergeTag()->willReturn(NULL);
    $root_component->requiredComponents()->willReturn([]);
    $root_component->getType()->willReturn('my_root');

    // The ClassHandler mock returns the generator mock.
    $class_handler->getGenerator(
      'my_root',
      Argument::that(function ($arg) use ($root_component_construction_data) {
        // Prophecy insists on the same array item order, so use a callback
        // so we don't have to care.
        // Assert the equality so thta we get nice output from PHPUnit for a
        // failure.
        $this->assertEquals($root_component_construction_data, $arg);

        return ($arg == $root_component_construction_data);
      })
    )->willReturn($root_component->reveal());

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal()
    );

    $component_list = $component_collector->assembleComponentList($root_data)->getComponents();
  }

  /**
   * Request with only the root generator, with a multi-valued preset.
   *
   * @group presets
   */
  public function testSingleGeneratorMultiPresetsNoRequirements() {
    $this->markTestSkipped();

    // The mocked root component's data info.
    $root_data_info = [
      // This property is assumed to exist by the collector.
      'root_name' => [
        'label' => 'Component machine name',
        'required' => TRUE,
      ],
      'preset_property' => [
        'label' => 'Label',
        'format' => 'array',
        'presets' => [
          'A' => [
            'label' => 'option_a',
            'data' => [
              'force' => [
                'forced_property' => [
                  'value' => ['forced_A'],
                ],
              ],
              'suggest' => [
                'suggested_property_filled' => [
                  'value' => ['suggested_A'],
                ],
                'suggested_property_empty' => [
                  'value' => ['suggested_A'],
                ],
              ],
            ],
          ],
          'B' => [
            'label' => 'option_b',
            'data' => [
              'force' => [
                'forced_property' => [
                  'value' => ['forced_B'],
                ],
              ],
              'suggest' => [
                'suggested_property_filled' => [
                  'value' => ['suggested_B'],
                ],
                'suggested_property_empty' => [
                  'value' => ['suggested_B'],
                ],
              ],
            ],
          ],
          'C' => [
            'label' => 'option_c',
            'data' => [
              'force' => [
                'forced_property' => [
                  'value' => ['forced_C'],
                ],
              ],
              'suggest' => [
                'suggested_property_filled' => [
                  'value' => ['suggested_C'],
                ],
                'suggested_property_empty' => [
                  'value' => ['suggested_C'],
                ],
              ],
            ],
          ],
        ],
      ],
      'forced_property' => [
        'format' => 'array',
      ],
      'suggested_property_filled' => [
        'format' => 'array',
      ],
      'suggested_property_empty' => [
        'format' => 'array',
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);
    // The request data we pass in to the system.
    $root_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'preset_property' => ['A', 'B'],
      // The user supplies a value for the suggested property.
      'suggested_property_filled' => ['user_filled'],
    ];
    // Expected data for the root component.
    // This is $root_data once it's been processed.
    $root_component_construction_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'preset_property' => ['A', 'B'],
      // The suggested value isn't used, because the user supplied a value.
      'suggested_property_filled' => ['user_filled'],
      // The force value is used.
      'forced_property' => ['forced_A', 'forced_B'],
      // The suggested value is used, because the user didn't supply a value.
      'suggested_property_empty' => ['suggested_A', 'suggested_B'],
      'component_type' => 'my_root',
    ];

    // Mock the ComponentCollector's injected dependencies.
    $environment = $this->prophesize('\DrupalCodeBuilder\Environment\EnvironmentInterface');
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);

    // Mock the root component generator, and methods on the dependencies which
    // return things relating to it.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getMergeTag()->willReturn(NULL);
    $root_component->requiredComponents()->willReturn([]);
    $root_component->getType()->willReturn('my_root');

    // The ClassHandler mock returns the generator mock.
    $class_handler->getGenerator(
      'my_root',
      Argument::that(function ($arg) use ($root_component_construction_data) {
        // Prophecy insists on the same array item order, so use a callback
        // so we don't have to care.
        // Assert the equality so thta we get nice output from PHPUnit for a
        // failure.
        $this->assertEquals($root_component_construction_data, $arg);

        return ($arg == $root_component_construction_data);
      })
    )->willReturn($root_component->reveal());

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal()
    );

    $component_list = $component_collector->assembleComponentList($root_data)->getComponents();
  }


  /**
   * Request with only the root generator, which has a child requirement.
   */
  public function testSingleGeneratorChildRequirements() {
    $this->markTestSkipped();

    // The mocked root component's data info.
    $root_data_info = [
      // This property is assumed to exist by the collector.
      'root_name' => [
        'label' => 'Component machine name',
        'required' => TRUE,
      ],
      'plain_property_string' => [
        'format' => 'string',
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);
    // The request data we pass in to the system.
    $root_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'plain_property_string' => 'string_value',
    ];

    // The component collector's injected dependencies.
    $environment = $this->prophesize('\DrupalCodeBuilder\Environment\EnvironmentInterface');
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);

    // The root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getMergeTag()->willReturn(NULL);
    $root_component->requiredComponents()->willReturn([
      'child_requirement' => [
        'component_type' => 'child_requirement',
      ]
    ]);
    $root_component->getType()->willReturn('my_root');

    $class_handler->getGenerator(
      'my_root',
      Argument::that(function ($arg) use ($root_data) {
        return empty(array_diff($root_data, $arg));
      })
    )->willReturn($root_component->reveal());

    // The child component the root component requests.
    $child_requirement_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $child_requirement_component->getMergeTag()->willReturn(NULL);
    $child_requirement_component->requiredComponents()->willReturn([]);

    $child_requirement_component_data_info = [];

    // Wildcard the data parameter. We're not testing what components receive
    // for construction.
    $class_handler->getGenerator(
      'child_requirement',
      Argument::that(function ($arg) {
        return empty(array_diff([
          'component_type' => 'child_requirement',
        ], $arg));
      })
    )->willReturn($child_requirement_component->reveal());

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal()
    );

    $component_paths = $component_collector->assembleComponentList($root_data)->getComponentRequestPaths();

    $this->assertCount(2, $component_paths, "The expected number of components is returned.");
    $this->assertContains('root', $component_paths, "The component list has the root generator.");
    $this->assertContains('root/child_requirement', $component_paths, "The component list has the child generator.");
  }

  /**
   * Request with only the root generator, which has grandchild requirements.
   */
  public function testSingleGeneratorGrandchildRequirements() {
    $this->markTestSkipped();

    // The mocked root component's data info.
    $root_data_info = [
      // This property is assumed to exist by the collector.
      'root_name' => [
        'label' => 'Component machine name',
        'required' => TRUE,
      ],
      'plain_property_string' => [
        'format' => 'string',
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);
    // The request data we pass in to the system.
    $root_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'plain_property_string' => 'string_value',
    ];

    // The component collector's injected dependencies.
    $environment = $this->prophesize('\DrupalCodeBuilder\Environment\EnvironmentInterface');
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);

    // The root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getMergeTag()->willReturn(NULL);
    $root_component->requiredComponents()->willReturn([
      'child_requirement' => [
        'component_type' => 'child_requirement',
      ]
    ]);
    $root_component->getType()->willReturn('my_root');

    $class_handler->getGenerator(
      'my_root',
      Argument::that(function ($arg) use ($root_data) {
        return empty(array_diff($root_data, $arg));
      })
    )->willReturn($root_component->reveal());

    // The child component the root component requests.
    $child_requirement_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $child_requirement_component->getMergeTag()->willReturn(NULL);
    $child_requirement_component->getType()->willReturn('child_requirement');
    $child_requirement_component->requiredComponents()->willReturn([
      'grandchild_requirement' => [
        'component_type' => 'grandchild_requirement',
      ]
    ]);

    $child_requirement_component_data_info = [];

    // Wildcard the data parameter. We're not testing what components receive
    // for construction.
    $class_handler->getGenerator(
      'child_requirement',
      Argument::that(function ($arg) {
        return empty(array_diff([
          'component_type' => 'child_requirement',
        ], $arg));
      })
    )->willReturn($child_requirement_component->reveal());

    // The grandchild component requested by the child.
    $grandchild_requirement_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $grandchild_requirement_component->getMergeTag()->willReturn(NULL);
    $grandchild_requirement_component->requiredComponents()->willReturn([]);

    $grandchild_requirement_component_data_info = [];

    // Wildcard the data parameter. We're not testing what components receive
    // for construction.
    $class_handler->getGenerator(
      'grandchild_requirement',
      Argument::that(function ($arg) {
        return empty(array_diff([
          'component_type' => 'grandchild_requirement',
        ], $arg));
      })
    )->willReturn($grandchild_requirement_component->reveal());

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal()
    );

    $component_paths = $component_collector->assembleComponentList($root_data)->getComponentRequestPaths();

    $this->assertCount(3, $component_paths, "The expected number of components is returned.");
    $this->assertContains('root', $component_paths, "The component list has the root generator.");
    $this->assertContains('root/child_requirement', $component_paths, "The component list has the root generator.");
    $this->assertContains('root/child_requirement/grandchild_requirement', $component_paths, "The component list has the root generator.");
  }

  /**
   * Request with the root generator and a boolean component property.
   */
  public function testBooleanChildComponentNoRequests() {
    $this->markTestSkipped();

    // The mocked root component's data info.
    $root_data_info = [
      // This property is assumed to exist by the collector.
      'root_name' => [
        'label' => 'Component machine name',
        'required' => TRUE,
      ],
      'plain_property_string' => [
        'format' => 'string',
      ],
      'component_property_simple' => [
        'format' => 'boolean',
        'component_type' => 'simple'
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);
    // The request data we pass in to the system.
    $root_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'plain_property_string' => 'string_value',
      'component_property_simple' => TRUE,
    ];

    $environment = $this->prophesize('\DrupalCodeBuilder\Environment\EnvironmentInterface');
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);

    // Root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getMergeTag()->willReturn(NULL);
    $root_component->getType()->willReturn('my_root');

    $class_handler->getGenerator(
      'my_root',
      Argument::that(function ($arg) use ($root_data) {
        // Use a wildcard rather than $root_data, the collector may add data.
        // Check that the param contains all the elements of $root_data.
        // (Can't use array_diff() that doesn't do nested arrays FFS!)
        foreach ($root_data as $key => $value) {
          if (!isset($arg[$key]) || $arg[$key] != $value) {
            return FALSE;
          }
        }
        return TRUE;
      })
    )
    ->will(function ($args) use ($root_component) {
      return $root_component->reveal();
    });
    $root_component->requiredComponents()->willReturn([]);

    // Simple child component.
    $simple_child_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $simple_child_component->getMergeTag()->willReturn(NULL);
    $simple_child_component->requiredComponents()->willReturn([]);

    $class_handler->getGenerator(
      'simple',
      Argument::that(function ($arg) {
        return empty(array_diff(["component_type" => "simple"], $arg));
      })
    )
    ->willReturn($simple_child_component->reveal());

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal()
    );

    $component_paths = $component_collector->assembleComponentList($root_data)->getComponentRequestPaths();

    $this->assertCount(2, $component_paths, "The expected number of components is returned.");
    $this->assertContains('root', $component_paths, "The component list has the root generator.");
    $this->assertContains('root/simple', $component_paths, "The component list has the child generator.");
  }

  /**
   * Request with the root generator and an array component property.
   */
  public function testArrayChildComponentNoRequests() {
    $this->markTestSkipped();

    // The mocked root component's data info.
    $root_data_info = [
      // This property is assumed to exist by the collector.
      'root_name' => [
        'label' => 'Component machine name',
        'required' => TRUE,
      ],
      'plain_property_string' => [
        'format' => 'string',
      ],
      'component_property_array' => [
        'format' => 'array',
        'component_type' => 'component_array'
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);

    // The child component data info.
    $component_array_data_info = [
      'primary_property' => [
        'primary' => TRUE,
      ],
    ];
    $this->componentDataInfoAddDefaults($component_array_data_info);

    // The request data we pass in to the system.
    $root_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'plain_property_string' => 'string_value',
      'component_property_array' => [
        'alpha',
        'beta',
      ],
    ];

    $environment = $this->prophesize('\DrupalCodeBuilder\Environment\EnvironmentInterface');
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);

    // Root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getMergeTag()->willReturn(NULL);
    $root_component->getType()->willReturn('my_root');

    $class_handler->getGenerator(
      'my_root',
      Argument::that(function ($arg) use ($root_data) {
        // Use a wildcard rather than $root_data, the collector may add data.
        // Check that the param contains all the elements of $root_data.
        // (Can't use array_diff() that doesn't do nested arrays FFS!)
        foreach ($root_data as $key => $value) {
          if (!isset($arg[$key]) || $arg[$key] != $value) {
            return FALSE;
          }
        }
        return TRUE;
      })
    )
    ->will(function ($args) use ($root_component) {
      return $root_component->reveal();
    });
    $root_component->requiredComponents()->willReturn([]);

    // Alpha child component.
    $alpha_child_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $alpha_child_component->getMergeTag()->willReturn(NULL);
    $alpha_child_component->requiredComponents()->willReturn([]);

    $class_handler->getGenerator(
      'component_array',
      [
        "component_type" => "component_array",
        "primary_property" => "alpha",
      ]
    )
    ->willReturn($alpha_child_component->reveal());

    // Beta child component.
    $beta_child_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $beta_child_component->getMergeTag()->willReturn(NULL);
    $beta_child_component->requiredComponents()->willReturn([]);

    $class_handler->getGenerator(
      'component_array',
      [
        "component_type" => "component_array",
        "primary_property" => "beta",
      ]
    )
    ->willReturn($beta_child_component->reveal());

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal()
    );

    $component_paths = $component_collector->assembleComponentList($root_data)->getComponentRequestPaths();

    $this->assertCount(3, $component_paths, "The expected number of components is returned.");
    $this->assertContains('root', $component_paths, "The component list has the root generator.");
    $this->assertContains('root/alpha', $component_paths, "The component list has the child generator.");
    $this->assertContains('root/beta', $component_paths, "The component list has the child generator.");
  }

  /**
   * Request with the root generator and a compound component property.
   */
  public function testCompoundChildPropertyNoRequests() {
    $this->markTestSkipped();

    // The mocked root component's data info.
    $root_data_info = [
      // This property is assumed to exist by the collector.
      'root_name' => [
        'label' => 'Component machine name',
        'required' => TRUE,
      ],
      'plain_property_string' => [
        'format' => 'string',
      ],
      'property_compound' => [
        'format' => 'compound',
        'properties' => [
          'child_property_string' => [
            'format' => 'string',
          ],
          'child_property_string_default' => [
            'format' => 'string',
            'default' => 'default_value',
          ],
          'child_property_string_process_default' => [
            'format' => 'string',
            'default' => 'default_value',
            'process_default' => TRUE,
          ],
          'child_property_internal' => [
            'default' => 'default_value',
            'internal' => TRUE,
          ],
          'child_property_computed' => [
            'default' => 'default_value',
            'computed' => TRUE,
          ],
        ],
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);
    // The request data we pass in to the system.
    $root_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'plain_property_string' => 'string_value',
      'property_compound' => [
        0 => [
          'child_property_string' => 'child_property_string:value_0',
          'child_property_string_default' => 'child_property_string_default:value_0',
          'child_property_string_process_default' => 'child_property_string_process_default:value_0',
        ],
        1 => [
          'child_property_string' => 'child_property_string:value_1',
        ],
        2 => [
          // Nothing.
          // This is a bit of an odd case: the presense of this item data array,
          // even though empty, will mean it gets process defaults and internal
          // values filled in.
        ],
      ],
    ];
    // Expected data for the root component.
    // This is $root_data once it's been processed.
    $root_component_construction_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'plain_property_string' => 'string_value',
      'property_compound' => [
        0 => [
          // These were speficied.
          'child_property_string' => 'child_property_string:value_0',
          'child_property_string_default' => 'child_property_string_default:value_0',
          'child_property_string_process_default' => 'child_property_string_process_default:value_0',
          // Internal properties get filled in.
          "child_property_internal" => "default_value",
          "child_property_computed" => "default_value",
        ],
        1 => [
          // This was specified.
          "child_property_string" => "child_property_string:value_1",
           // These are filled in.
          "child_property_string_process_default" => "default_value",
          "child_property_internal" => "default_value",
          "child_property_computed" => "default_value",
        ],
        2 => [
          // These are filled in.
         "child_property_string_process_default" => "default_value",
         "child_property_internal" => "default_value",
         "child_property_computed" => "default_value",
        ],
      ],
      'component_type' => 'my_root',
    ];

    $environment = $this->prophesize('\DrupalCodeBuilder\Environment\EnvironmentInterface');
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);

    // Root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getMergeTag()->willReturn(NULL);
    $root_component->getType()->willReturn('my_root');

    $class_handler->getGenerator(
      'my_root',
      $root_component_construction_data
    )
    ->will(function ($args) use ($root_component) {
      return $root_component->reveal();
    });
    $root_component->requiredComponents()->willReturn([]);

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal()
    );

    $component_paths = $component_collector->assembleComponentList($root_data)->getComponentRequestPaths();

    $this->assertCount(1, $component_paths, "The expected number of components is returned.");
    $this->assertContains('root', $component_paths, "The component list has the root generator.");
  }

  /**
   * Test acquired property values.
   */
  public function testAcquiredValues() {
    $this->markTestSkipped();

    // The mocked root component's data info.
    $root_data_info = [
      // This property is assumed to exist by the collector.
      'root_name' => [
        'label' => 'Component machine name',
        'required' => TRUE,
      ],
      'acquired_verbatim' => [
        'format' => 'string',
      ],
      'acquired_aliased' => [
        'format' => 'string',
        'acquired_alias' => 'acquired_from_alias',
      ],
      'acquired_from_source' => [
        'format' => 'string',
      ],
      // This is just a dummy to test that 'acquired_from_source' is used
      // instead of this.
      'acquired_from_specified' => [
        'format' => 'string',
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);
    // The request data we pass in to the system.
    $root_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'acquired_verbatim' => 'acquired_verbatim_value',
      'acquired_aliased' => 'acquired_aliased_value',
      'acquired_from_source' => 'acquired_from_source_value',
      'acquired_from_specified' => 'acquired_from_specified_value',
    ];

    // The component collector's injected dependencies.
    $environment = $this->prophesize('\DrupalCodeBuilder\Environment\EnvironmentInterface');
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);

    // The root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getMergeTag()->willReturn(NULL);
    $root_component->requiredComponents()->willReturn([
      'child_requirement' => [
        'component_type' => 'child_requirement',
      ]
    ]);
    $root_component->getType()->willReturn('my_root');
    $root_component->getComponentDataValue("acquired_verbatim")->willReturn('acquired_verbatim_value');
    $root_component->getComponentDataValue("acquired_aliased")->willReturn('acquired_aliased_value');
    $root_component->getComponentDataValue("acquired_from_source")->willReturn('acquired_from_source_value');

    $class_handler->getGenerator(
      'my_root',
      Argument::that(function ($arg) use ($root_data) {
        return empty(array_diff($root_data, $arg));
      })
    )->willReturn($root_component->reveal());

    // The child component the root component requests.
    $child_requirement_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $child_requirement_component->getMergeTag()->willReturn(NULL);
    $child_requirement_component->requiredComponents()->willReturn([]);

    $child_requirement_component_data_info = [
      'acquired_verbatim' => [
        'format' => 'string',
        'acquired' => TRUE,
      ],
      'acquired_from_alias' => [
        'format' => 'string',
        'acquired' => TRUE,
      ],
      'acquired_from_specified' => [
        'format' => 'string',
        'acquired' => TRUE,
        'acquired_from' => 'acquired_from_source',
      ],
    ];
    $this->componentDataInfoAddDefaults($child_requirement_component_data_info);

    // Wildcard the data parameter. We're not testing what components receive
    // for construction.
    $class_handler->getGenerator(
      'child_requirement',
      Argument::that(function ($arg) {
        // Use a PHPUnit assertion because its error output is much better
        // than prophecy's.
        $this->assertEquals([
          'component_type' => 'child_requirement',
          // Acquired from root values.
          'acquired_verbatim' => 'acquired_verbatim_value',
          // Acquired from root values, with an alias.
          'acquired_from_alias' => 'acquired_aliased_value',
          // Acquired from specified property.
          'acquired_from_specified' => 'acquired_from_source_value',
        ], $arg, "The child component is created with the expected data.");
        return TRUE;
      })
    )->willReturn($child_requirement_component->reveal());

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal()
    );

    $component_paths = $component_collector->assembleComponentList($root_data)->getComponentRequestPaths();

    $this->assertCount(2, $component_paths, "The expected number of components is returned.");
    $this->assertContains('root', $component_paths, "The component list has the root generator.");
    $this->assertContains('root/child_requirement', $component_paths, "The component list has the child generator.");
  }

  /*
  Further tests todo:
  - basic properties, single generator makes requests
  - basic properties, grandchild requests
  - repeat requests
  - ....?
  */

  /**
   * Add in default values for property info keys.
   *
   * @param &$data_info
   *   A data info array, passed by reference and altered in place.
   */
  protected function componentDataInfoAddDefaults(&$data_info) {
    foreach ($data_info as &$property_info) {
      $property_info += [
        'required' => FALSE,
        'format' => 'string',
      ];

      // Recurse into child properties.
      if (isset($property_info['properties'])) {
        $this->componentDataInfoAddDefaults($property_info['properties']);
      }
    }
  }

}
