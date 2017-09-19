<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the retrieval of of component data from component classes.
 */
class APIComponentDataInfoTest extends TestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  public function testComponentDataInfo() {
    // Mock a Generator class.
    $mock_root = \Mockery::mock('alias:' . \DrupalCodeBuilder\Generator\Root::class)->makePartial();
    $mock_root->allows([
      'componentDataDefinition' => [
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
        // Compound. Will get the properties from the Child class mock.
        'property_compound' => [
          'label' => 'Compound',
          'format' => 'compound',
          'component' => 'Child',
        ],
      ],
    ]);
    $mock_root->allows([
      'getSanityLevel' => ['none'],
    ]);

    $mock_child = \Mockery::mock('alias:' . \DrupalCodeBuilder\Generator\Child::class)->makePartial();
    $mock_child->allows([
      'componentDataDefinition' => [
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
      ],
    ]);

    // Get the Generate task, with our mock as the root component.
    $generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'root');

    $info = $generate->getRootComponentDataInfo();

    $this->assertArrayHasKey('property_public', $info, "The public property is returned.");
    $this->assertArrayHasKey('property_public_format', $info, "The public property is returned.");
    $this->assertArrayHasKey('property_compound', $info, "The compound property is returned.");
    $this->assertArrayNotHasKey('property_computed', $info, "The computed property is not returned.");

    $this->assertEquals('string', $info['property_public']['format'], "The default format is filled in.");
    $this->assertEquals(FALSE, $info['property_public']['required'], "The default required is filled in.");

    $this->assertEquals('boolean', $info['property_public_format']['format'], "The specified format is preserved.");
    $this->assertEquals(TRUE, $info['property_public_required']['required'], "The specified required is preserved.");

    $this->assertEquals('Child', $info['property_compound']['component'], "The compound property specifies the component.");
    $this->assertEquals('compound', $info['property_compound']['format'], "The compound property specifies the format.");
    $this->assertArrayHasKey('properties', $info['property_compound'], "The compound property has an array of child properties.");

    $child_info = $info['property_compound']['properties'];

    $this->assertArrayHasKey('property_child_public', $child_info, "The public property is returned.");
    $this->assertArrayHasKey('property_child_format', $child_info, "The format property is returned.");
    $this->assertArrayHasKey('property_child_required', $child_info, "The required property is returned.");
    $this->assertArrayNotHasKey('property_child_computed', $child_info, "The computed property is not returned.");

    $this->assertEquals('string', $child_info['property_child_public']['format'], "The default format is filled in.");
    $this->assertEquals(FALSE, $child_info['property_child_public']['required'], "The default required is filled in.");

    $this->assertEquals('boolean', $child_info['property_child_format']['format'], "The specified format is preserved.");
    $this->assertEquals(TRUE, $child_info['property_child_required']['required'], "The specified required is preserved.");
  }

  public function tearDown() {
    \Mockery::close();
  }

}
