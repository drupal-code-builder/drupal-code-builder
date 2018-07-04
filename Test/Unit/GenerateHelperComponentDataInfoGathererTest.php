<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the preparation of component data from component classes.
 *
 * This tests data definitions from BaseGenerator::componentDataDefinition()
 * are properly prepared for consumption by UIs by ComponentDataInfoGatherer.
 */
class GenerateHelperComponentDataInfoGathererTest extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  public function testComponentDataInfo() {
    // Mocked component data info.
    $root_component_data_info = [
      // Specifies only label, receives defaults.
      'property_public' => [
        'label' => 'Public',
      ],
      // Specifies its format.
      'property_public_format' => [
        'label' => 'Public',
        'format' => 'boolean',
      ],
      // Specifies required.
      'property_public_required' => [
        'label' => 'Public',
        'required' => TRUE,
      ],
      // Computed.
      'property_computed' => [
        'label' => 'Computed',
        'computed' => TRUE,
      ],
      // Internal.
      'property_internal' => [
        'label' => 'Internal',
        'internal' => TRUE,
      ],
      // Acquired.
      'property_acquired' => [
        'acquired' => TRUE,
      ],
      // Compound using a component. Will get the properties from the Child
      // class mock.
      'property_compound_component' => [
        'label' => 'Compound component',
        'format' => 'compound',
        'component_type' => 'Child',
      ],
      // Compound with child properties.
      'property_compound_child' => [
        'label' => 'Compound child',
        'format' => 'compound',
        'properties' => [
          'property_child_public' => [
            'label' => 'Public',
          ],
          'property_child_format' => [
            'label' => 'Public',
            'format' => 'boolean',
          ],
          'property_child_required' => [
            'label' => 'Public',
            'required' => TRUE,
          ],
          'property_child_computed' => [
            'label' => 'Computed',
            'computed' => TRUE,
          ],
          'property_child_internal' => [
            'label' => 'Internal',
            'internal' => TRUE,
          ],
        ],
      ],
    ];

    $child_component_data_info = [
      'property_child_public' => [
        'label' => 'Public',
      ],
      'property_child_format' => [
        'label' => 'Public',
        'format' => 'boolean',
      ],
      'property_child_required' => [
        'label' => 'Public',
        'required' => TRUE,
      ],
      'property_child_computed' => [
        'label' => 'Computed',
        'computed' => TRUE,
      ],
      'property_child_internal' => [
        'label' => 'Internal',
        'internal' => TRUE,
      ],
      'property_child_acquired' => [
        'acquired' => TRUE,
      ],
    ];

    // Mock the class handler to return the data info for the root and child
    // components.
    $class_handler = $this->prophesize(\DrupalCodeBuilder\Task\Generate\ComponentClassHandler::class);
    $class_handler->getComponentDataDefinition('Root')->willReturn($root_component_data_info);
    $class_handler->getComponentDataDefinition('Child')->willReturn($child_component_data_info);

    // Create the helper, with mocks passed in.
    $data_info_gatherer = new \DrupalCodeBuilder\Task\Generate\ComponentDataInfoGatherer(
      $class_handler->reveal()
    );

    // Get the prepared data info from the gatherer.
    $info = $data_info_gatherer->getComponentDataInfo('Root');

    $this->assertArrayHasKey('property_public', $info, "The public property is returned.");
    $this->assertArrayHasKey('property_public_format', $info, "The public property is returned.");
    $this->assertArrayHasKey('property_compound_component', $info, "The compound property is returned.");
    $this->assertArrayNotHasKey('property_computed', $info, "The computed property is not returned.");
    $this->assertArrayNotHasKey('property_internal', $info, "The internal property is not returned.");
    $this->assertArrayNotHasKey('property_acquired', $info, "The acquired property is not returned.");

    $this->assertEquals('string', $info['property_public']['format'], "The default format is filled in.");
    $this->assertEquals(FALSE, $info['property_public']['required'], "The default required is filled in.");

    $this->assertEquals('boolean', $info['property_public_format']['format'], "The specified format is preserved.");
    $this->assertEquals(TRUE, $info['property_public_required']['required'], "The specified required is preserved.");

    $this->assertEquals('Child', $info['property_compound_component']['component_type'], "The compound property specifies the component.");
    $this->assertEquals('compound', $info['property_compound_component']['format'], "The compound property specifies the format.");
    $this->assertArrayHasKey('properties', $info['property_compound_component'], "The compound property has an array of child properties.");

    $component_child_info = $info['property_compound_component']['properties'];

    $this->assertArrayHasKey('property_child_public', $component_child_info, "The public property is returned.");
    $this->assertArrayHasKey('property_child_format', $component_child_info, "The format property is returned.");
    $this->assertArrayHasKey('property_child_required', $component_child_info, "The required property is returned.");
    $this->assertArrayNotHasKey('property_child_computed', $component_child_info, "The computed property is not returned.");
    $this->assertArrayNotHasKey('property_child_internal', $component_child_info, "The internal property is not returned.");
    $this->assertArrayNotHasKey('property_child_acquired', $component_child_info, "The acquired property is not returned.");

    $this->assertEquals('string', $component_child_info['property_child_public']['format'], "The default format is filled in.");
    $this->assertEquals(FALSE, $component_child_info['property_child_public']['required'], "The default required is filled in.");

    $this->assertEquals('boolean', $component_child_info['property_child_format']['format'], "The specified format is preserved.");
    $this->assertEquals(TRUE, $component_child_info['property_child_required']['required'], "The specified required is preserved.");

    $child_property_info = $info['property_compound_child']['properties'];

    $this->assertArrayHasKey('property_child_public', $child_property_info, "The public property is returned.");
    $this->assertArrayHasKey('property_child_format', $child_property_info, "The format property is returned.");
    $this->assertArrayHasKey('property_child_required', $child_property_info, "The required property is returned.");
    $this->assertArrayNotHasKey('property_child_computed', $child_property_info, "The computed property is not returned.");
    $this->assertArrayNotHasKey('property_child_internal', $child_property_info, "The internal property is not returned.");

    $this->assertEquals('string', $child_property_info['property_child_public']['format'], "The default format is filled in.");
    $this->assertEquals(FALSE, $child_property_info['property_child_public']['required'], "The default required is filled in.");

    $this->assertEquals('boolean', $child_property_info['property_child_format']['format'], "The specified format is preserved.");
    $this->assertEquals(TRUE, $child_property_info['property_child_required']['required'], "The specified required is preserved.");
  }

}
