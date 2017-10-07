<?php

namespace DrupalCodeBuilder\Test\Unit;

use Prophecy\Argument;

/**
 * Unit test for the ComponentCollector Generate helper.
 */
class GenerateHelperComponentCollectorTest extends TestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Request with only the root generator, which itself has no requirements.
   */
  public function testSingleGeneratorNoRequirements() {
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
      'plain_property_default' => [
        'default' => 'default_value',
      ],
      'plain_property_process_default' => [
        'default' => 'default_value',
        'process_default' => TRUE,
      ],
      'plain_property_internal' => [
        'default' => 'default_value',
        'internal' => TRUE,
      ],
      'plain_property_computed' => [
        'default' => 'default_value',
        'computed' => TRUE,
      ],
      'plain_property_processing' => [
        'processing' => function($value, &$component_data, &$property_info) {
          $component_data['plain_property_processing'] = 'processed_value:' . $value;
        },
      ],
      // Test processing works on the default value.
      'plain_property_process_default_processing' => [
        'default' => 'default_value',
        'process_default' => TRUE,
        'processing' => function($value, &$component_data, &$property_info) {
          $component_data['plain_property_process_default_processing'] = 'processed_value:' . $value;
        },
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);
    // The request data we pass in to the system.
    $root_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'plain_property_string' => 'string_value',
      // We don't supply plain_property_default, and it does not get set.
      // We don't supply plain_property_process_default, and because it has
      // 'process_default' set, its value gets filled in.
      'plain_property_processing' => 'value_for_processing',
    ];
    // Expected data for the root component.
    // This is $root_data once it's been processed.
    $root_component_construction_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'plain_property_string' => 'string_value',
      'plain_property_process_default' => 'default_value',
      'plain_property_internal' => 'default_value',
      'plain_property_computed' => 'default_value',
      'plain_property_processing' => 'processed_value:value_for_processing',
      'plain_property_process_default_processing' => 'processed_value:default_value',
      'component_type' => 'my_root',
    ];

    // Mock the ComponentCollector's injected dependencies.
    $environment = $this->prophesize('\DrupalCodeBuilder\Environment\EnvironmentInterface');
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);
    $data_info_gatherer = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentDataInfoGatherer::class);

    // Mock the root component generator, and methods on the dependencies which
    // return things relating to it.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getUniqueID()->willReturn('root:root');
    $root_component->requiredComponents()->willReturn([]);

    // The ClassHandler mock returns the generator mock.
    $class_handler->getGenerator(
      'my_root',
      'my_root',
      $root_component_construction_data,
      NULL
    )->willReturn($root_component->reveal());

    // The ComponentDataInfoGatherer mock returns the generator's info.
    $data_info_gatherer->getComponentDataInfo('my_root', TRUE)->willReturn($root_data_info);

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal(),
      $data_info_gatherer->reveal()
    );

    $component_list = $component_collector->assembleComponentList($root_data);

    $this->assertCount(1, $component_list, "The expected number of components is returned.");
    $this->assertArrayHasKey('root:root', $component_list, "The component list has the root generator.");
  }

  /**
   * Request with only the root generator, which has a child requirement.
   */
  public function testSingleGeneratorChildRequirements() {
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
    $data_info_gatherer = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentDataInfoGatherer::class);

    // The root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getUniqueID()->willReturn('root:root');
    $root_component->requiredComponents()->willReturn([
      'child_requirement' => [
        'component_type' => 'child_requirement',
      ]
    ]);

    $data_info_gatherer->getComponentDataInfo('my_root', TRUE)->willReturn($root_data_info);
    $class_handler->getGenerator(
      'my_root',
      'my_root',
      Argument::that(function ($arg) use ($root_data) {
        return empty(array_diff($root_data, $arg));
      }),
      NULL
    )->willReturn($root_component->reveal());

    // The child component the root component requests.
    $child_requirement_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $child_requirement_component->getUniqueID()->willReturn('child:child');
    $child_requirement_component->requiredComponents()->willReturn([]);

    $child_requirement_component_data_info = [];

    $data_info_gatherer->getComponentDataInfo('child_requirement', TRUE)->willReturn($child_requirement_component_data_info);
    // Wildcard the data parameter. We're not testing what components receive
    // for construction.
    $class_handler->getGenerator(
      'child_requirement',
      'child_requirement',
      Argument::that(function ($arg) {
        return empty(array_diff([
          'component_type' => 'child_requirement',
        ], $arg));
      }),
      $root_component->reveal()
    )->willReturn($child_requirement_component->reveal());

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal(),
      $data_info_gatherer->reveal()
    );

    $component_list = $component_collector->assembleComponentList($root_data);

    $this->assertCount(2, $component_list, "The expected number of components is returned.");
    $this->assertArrayHasKey('root:root', $component_list, "The component list has the root generator.");
    $this->assertArrayHasKey('child:child', $component_list, "The component list has the root generator.");
  }

  /**
   * Request with only the root generator, which has grandchild requirements.
   */
  public function testSingleGeneratorGrandchildRequirements() {
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
    $data_info_gatherer = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentDataInfoGatherer::class);

    // The root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getUniqueID()->willReturn('root:root');
    $root_component->requiredComponents()->willReturn([
      'child_requirement' => [
        'component_type' => 'child_requirement',
      ]
    ]);

    $data_info_gatherer->getComponentDataInfo('my_root', TRUE)->willReturn($root_data_info);
    $class_handler->getGenerator(
      'my_root',
      'my_root',
      Argument::that(function ($arg) use ($root_data) {
        return empty(array_diff($root_data, $arg));
      }),
      NULL
    )->willReturn($root_component->reveal());

    // The child component the root component requests.
    $child_requirement_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $child_requirement_component->getUniqueID()->willReturn('child:child');
    $child_requirement_component->requiredComponents()->willReturn([
      'grandchild_requirement' => [
        'component_type' => 'grandchild_requirement',
      ]
    ]);

    $child_requirement_component_data_info = [];

    $data_info_gatherer->getComponentDataInfo('child_requirement', TRUE)->willReturn($child_requirement_component_data_info);
    // Wildcard the data parameter. We're not testing what components receive
    // for construction.
    $class_handler->getGenerator(
      'child_requirement',
      'child_requirement',
      Argument::that(function ($arg) {
        return empty(array_diff([
          'component_type' => 'child_requirement',
        ], $arg));
      }),
      $root_component->reveal()
    )->willReturn($child_requirement_component->reveal());

    // The grandchild component requested by the child.
    $grandchild_requirement_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $grandchild_requirement_component->getUniqueID()->willReturn('grandchild:grandchild');
    $grandchild_requirement_component->requiredComponents()->willReturn([]);

    $grandchild_requirement_component_data_info = [];

    $data_info_gatherer->getComponentDataInfo('grandchild_requirement', TRUE)->willReturn($grandchild_requirement_component_data_info);
    // Wildcard the data parameter. We're not testing what components receive
    // for construction.
    $class_handler->getGenerator(
      'grandchild_requirement',
      'grandchild_requirement',
      Argument::that(function ($arg) {
        return empty(array_diff([
          'component_type' => 'grandchild_requirement',
        ], $arg));
      }),
      $root_component->reveal()
    )->willReturn($grandchild_requirement_component->reveal());

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal(),
      $data_info_gatherer->reveal()
    );

    $component_list = $component_collector->assembleComponentList($root_data);

    $this->assertCount(3, $component_list, "The expected number of components is returned.");
    $this->assertArrayHasKey('root:root', $component_list, "The component list has the root generator.");
    $this->assertArrayHasKey('child:child', $component_list, "The component list has the root generator.");
    $this->assertArrayHasKey('grandchild:grandchild', $component_list, "The component list has the root generator.");
  }

  /**
   * Request with the root generator and a boolean component property.
   */
  public function testBooleanChildComponentNoRequests() {
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
        'component' => 'simple'
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
    $data_info_gatherer = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentDataInfoGatherer::class);

    // Root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getUniqueID()->willReturn('root:root');

    $class_handler->getGenerator(
      'my_root',
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
      }),
      NULL
    )
    ->will(function ($args) use ($root_component) {
      return $root_component->reveal();
    });
    $root_component->requiredComponents()->willReturn([]);


    $data_info_gatherer->getComponentDataInfo('my_root', TRUE)->willReturn($root_data_info);

    // Simple child component.
    $simple_child_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $simple_child_component->getUniqueID()->willReturn('simple:simple');
    $simple_child_component->requiredComponents()->willReturn([]);

    $class_handler->getGenerator(
      'simple',
      // Singleton, so its name is not prefixed apparently. Could be an
      // inconsistency for component properties...
      'simple',
      Argument::that(function ($arg) {
        return empty(array_diff(["component_type" => "simple"], $arg));
      }),
      $root_component
    )
    ->willReturn($simple_child_component->reveal());

    $data_info_gatherer->getComponentDataInfo('simple', TRUE)->willReturn([]);
    $class_handler->getRepeatComponentHandling('simple')->willReturn('singleton');

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal(),
      $data_info_gatherer->reveal()
    );

    $component_list = $component_collector->assembleComponentList($root_data);

    $this->assertCount(2, $component_list, "The expected number of components is returned.");
    $this->assertArrayHasKey('root:root', $component_list, "The component list has the root generator.");
    $this->assertArrayHasKey('simple:simple', $component_list, "The component list has the simple property generator.");
  }

  /**
   * Request with the root generator and an array component property.
   */
  public function testArrayChildComponentNoRequests() {
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
        'component' => 'array'
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);
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
    $data_info_gatherer = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentDataInfoGatherer::class);

    // Root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getUniqueID()->willReturn('root:root');

    $class_handler->getGenerator(
      'my_root',
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
      }),
      NULL
    )
    ->will(function ($args) use ($root_component) {
      return $root_component->reveal();
    });
    $root_component->requiredComponents()->willReturn([]);

    $data_info_gatherer->getComponentDataInfo('my_root', TRUE)->willReturn($root_data_info);

    // Alpha child component.
    $alpha_child_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $alpha_child_component->getUniqueID()->willReturn('array:alpha');
    $alpha_child_component->requiredComponents()->willReturn([]);

    $class_handler->getGenerator(
      'array',
      'alpha',
      Argument::that(function ($arg) {
        return empty(array_diff(["component_type" => "array"], $arg));
      }),
      $root_component
    )
    ->willReturn($alpha_child_component->reveal());

    // Beta child component.
    $beta_child_component = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $beta_child_component->getUniqueID()->willReturn('array:beta');
    $beta_child_component->requiredComponents()->willReturn([]);

    $class_handler->getGenerator(
      'array',
      'beta',
      Argument::that(function ($arg) {
        return empty(array_diff(["component_type" => "array"], $arg));
      }),
      $root_component
    )
    ->willReturn($beta_child_component->reveal());

    // Components which are used for an 'array' format property have no
    // properties of their own, since they get created with just the single
    // array value as their name.
    $data_info_gatherer->getComponentDataInfo('array', TRUE)->willReturn([]);
    $class_handler->getRepeatComponentHandling('array')->willReturn('repeat');

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal(),
      $data_info_gatherer->reveal()
    );

    $component_list = $component_collector->assembleComponentList($root_data);

    $this->assertCount(3, $component_list, "The expected number of components is returned.");
    $this->assertArrayHasKey('root:root', $component_list, "The component list has the root generator.");
    $this->assertArrayHasKey('array:alpha', $component_list, "The component list has the alpha array generator.");
    $this->assertArrayHasKey('array:beta', $component_list, "The component list has the beta array generator.");
  }

  /**
   * Request with the root generator and a compound component property.
   */
  public function testCompoundChildComponentNoRequests() {
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
      'component_property_compound' => [
        'format' => 'compound',
        'component' => 'compound'
      ],
    ];
    $this->componentDataInfoAddDefaults($root_data_info);
    // The request data we pass in to the system.
    $root_data = [
      'base' => 'my_root',
      'root_name' => 'my_component',
      'plain_property_string' => 'string_value',
      'component_property_compound' => [
        0 => [
          'child_property_string' => 'child_string_value_0',
        ],
        1 => [
          'child_property_string' => 'child_string_value_1',
        ],
      ],
    ];

    $environment = $this->prophesize('\DrupalCodeBuilder\Environment\EnvironmentInterface');
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);
    $data_info_gatherer = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentDataInfoGatherer::class);

    // Root component.
    $root_component = $this->prophesize(\DrupalCodeBuilder\Generator\RootComponent::class);

    $root_component->getUniqueID()->willReturn('root:root');

    $class_handler->getGenerator(
      'my_root',
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
      }),
      NULL
    )
    ->will(function ($args) use ($root_component) {
      return $root_component->reveal();
    });
    $root_component->requiredComponents()->willReturn([]);

    $data_info_gatherer->getComponentDataInfo('my_root', TRUE)->willReturn($root_data_info);

    // Compound child component 0.
    $compound_child_component_0 = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $compound_child_component_0->getUniqueID()->willReturn('compound:compound_0');
    $compound_child_component_0->requiredComponents()->willReturn([]);

    $class_handler->getGenerator(
      'compound',
      // Singleton, so its name is not prefixed apparently. Could be an
      // inconsistency for component properties...
      'compound_0',
      [
        "child_property_string" => "child_string_value_0",
        "component_type" => "compound",
        "root_component_name" => "my_component",
      ],
      $root_component
    )
    ->willReturn($compound_child_component_0->reveal());

    $data_info_gatherer->getComponentDataInfo('compound', TRUE)->willReturn([]);
    $class_handler->getRepeatComponentHandling('compound')->shouldNotBeCalled();

    // Compound child component 1.
    $compound_child_component_1 = $this->prophesize(\DrupalCodeBuilder\Generator\BaseGenerator::class);

    $compound_child_component_1->getUniqueID()->willReturn('compound:compound_1');
    $compound_child_component_1->requiredComponents()->willReturn([]);

    $class_handler->getGenerator(
      'compound',
      // Singleton, so its name is not prefixed apparently. Could be an
      // inconsistency for component properties...
      'compound_1',
      [
        "child_property_string" => "child_string_value_1",
        "component_type" => "compound",
        "root_component_name" => "my_component",
      ],
      $root_component
    )
    ->willReturn($compound_child_component_1->reveal());

    // Create the helper, with mocks passed in.
    $component_collector = new \DrupalCodeBuilder\Task\Generate\ComponentCollector(
      $environment->reveal(),
      $class_handler->reveal(),
      $data_info_gatherer->reveal()
    );

    $component_list = $component_collector->assembleComponentList($root_data);

    $this->assertCount(3, $component_list, "The expected number of components is returned.");

    $this->assertArrayHasKey('root:root', $component_list, "The component list has the root generator.");
    $this->assertArrayHasKey('compound:compound_0', $component_list, "The component list has the simple property generator.");
    $this->assertArrayHasKey('compound:compound_1', $component_list, "The component list has the simple property generator.");
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
   * This does the work of
   * ComponentDataInfoGatherer::componentDataInfoAddDefaults() as we mock
   * that helper.
   *
   * @param &$data_info
   *   A data info array, passed by reference and altered in place.
   */
  protected function componentDataInfoAddDefaults(&$data_info) {
    foreach ($data_info as &$property_info) {
      $property_info += array(
        'required' => FALSE,
        'format' => 'string',
      );

      // Recurse into child properties.
      if (isset($property_info['properties'])) {
        $this->componentDataInfoAddDefaults($property_info['properties']);
      }
    }
  }

}
