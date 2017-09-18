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
      ],
    ]);
    $mock_root->allows([
      'getSanityLevel' => ['none'],
    ]);

    // Get the Generate task, with our mock as the root component.
    $generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'root');

    $info = $generate->getRootComponentDataInfo();

    $this->assertArrayHasKey('property_public', $info, "The public property is returned.");
    $this->assertArrayHasKey('property_public_format', $info, "The public property is returned.");
    $this->assertArrayNotHasKey('property_computed', $info, "The computed property is not returned.");

    $this->assertEquals('string', $info['property_public']['format'], "The default format is filled in.");
    $this->assertEquals(FALSE, $info['property_public']['required'], "The default required is filled in.");

    $this->assertEquals('boolean', $info['property_public_format']['format'], "The specified format is preserved.");
    $this->assertEquals(TRUE, $info['property_public_required']['required'], "The specified required is preserved.");
  }

  public function tearDown() {
    \Mockery::close();
  }

}
