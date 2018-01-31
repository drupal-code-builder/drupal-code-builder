<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the preparation step of component data.
 *
 * This tests that when UIs call prepareComponentDataProperty() on component
 * data info, options and defaults are properly set up.
 */
class GenerateHelperComponentPropertyPreparerTest extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test defaults in component data.
   */
  function testComponentDataDefaults() {
    // A test array of component data info.
    // This should be in the same format as returned from
    // Generate::getRootComponentDataInfo(), so with keys such as 'format'
    // and 'required' always populated.
    $component_data_info = [
      'no_default_string' => [
        'label' => 'Label',
        'format' => 'string',
        'required' => FALSE,
      ],
      'no_default_array' => [
        'label' => 'Label',
        'format' => 'array',
        'required' => FALSE,
      ],
      'has_default_fixed' => [
        'label' => 'Label',
        'default' => 'has_default_default',
        // We don't go through the system to fill in default info properties
        // here, so need them hardcoded.
        'required' => FALSE,
        'format' => 'string',
      ],
      'has_default_callable' => [
        'label' => 'Label',
        'default' => function($component_data) {
          return $component_data['has_default_fixed'] . '_called';
        },
        'required' => FALSE,
        'format' => 'string',
      ],
      'has_options' => [
        'label' => 'Label',
        'options' => function(&$property_info) {
          return [
            'A' => 'option_a',
            'B' => 'option_b',
          ];
        },
        'required' => FALSE,
        'format' => 'string',
      ],
      'has_presets' => [
        'label' => 'Label',
        'presets' => [
          'A' => [
            'label' => 'option_a',
            'data' => []
          ],
          'B' => [
            'label' => 'option_b',
            'data' => []
          ],
        ],
        'required' => FALSE,
        'format' => 'string',
      ],
      'compound_defaults' => [
        'label' => 'Compound properties',
        'properties' => [
          'compound_has_default_fixed' => [
            'label' => 'Label',
            'default' => 'compound_has_default_fixed_default_value',
            'required' => FALSE,
            'format' => 'string',
            // These are here to test that repeated preparing of this property
            // for several child items handles only expanding the options once.
            'options' => function(&$property_info) {
              return [
                'A' => 'option_a',
                'B' => 'option_b',
              ];
            },
          ],
          'compound_has_default_callable' => [
            'label' => 'Label',
            'default' => function($component_data) {
              return $component_data['compound_has_default_fixed'] . '_called';
            },
            'required' => FALSE,
            'format' => 'string',
          ],
        ],
      ],
      'compound_options' => [
        'label' => 'Compound properties',
        'properties' => [
          'compound_has_options' => [
            'label' => 'Label',
            'options' => function(&$property_info) {
              return [
                'A' => 'option_a',
                'B' => 'option_b',
              ];
            },
            'required' => FALSE,
            'format' => 'string',
          ],
        ],
        'required' => FALSE,
        'format' => 'compound',
      ]
    ];

    // Create the helper.
    $component_property_preparer = new \DrupalCodeBuilder\Task\Generate\ComponentPropertyPreparer();

    $component_data = [];

    // Component data gets default values set if empty.
    // Simple properties.
    // Properties with no default still get something set into the data array.
    $component_property_preparer->prepareComponentDataProperty('no_default_string', $component_data_info['no_default_string'], $component_data);
    $this->assertArrayHasKey('no_default_string', $component_data,
      "The empty string default value was set in the component data.");
    $this->assertEquals($component_data['no_default_string'], '',
      "The empty string default value was set in the component data.");

    $component_property_preparer->prepareComponentDataProperty('no_default_array', $component_data_info['no_default_array'], $component_data);
    $this->assertArrayHasKey('no_default_array', $component_data,
      "The empty array default value was set in the component data.");
    $this->assertEquals($component_data['no_default_array'], [],
      "The empty array default value was set in the component data.");

    // Properties with an actual default value.
    $component_property_preparer->prepareComponentDataProperty('has_default_fixed', $component_data_info['has_default_fixed'], $component_data);
    $this->assertArrayHasKey('has_default_fixed', $component_data,
      "The fixed default value was set in the component data.");
    $this->assertEquals($component_data['has_default_fixed'], 'has_default_default',
      "The fixed default value was set in the component data.");

    $component_property_preparer->prepareComponentDataProperty('has_default_callable', $component_data_info['has_default_callable'], $component_data);
    $this->assertArrayHasKey('has_default_callable', $component_data,
      "The default value from a callable was set in the component data.");
    $this->assertEquals($component_data['has_default_callable'], 'has_default_default_called',
      "The default value from a callable was set in the component data.");

    // Compound properties.
    // Initialize a child item for the compound property: defaults will only be
    // set if this exists.
    $component_data['compound_defaults'][0] = [];
    $component_property_preparer->prepareComponentDataProperty(
      'compound_has_default_fixed',
      $component_data_info['compound_defaults']['properties']['compound_has_default_fixed'],
      $component_data['compound_defaults'][0]
    );
    $this->assertArrayHasKey('compound_has_default_fixed', $component_data['compound_defaults'][0],
      "The fixed default value was set in the component data child item.");
    $this->assertEquals('compound_has_default_fixed_default_value', $component_data['compound_defaults'][0]['compound_has_default_fixed'],
      "The fixed default value was set in the component data child item.");

    $component_property_preparer->prepareComponentDataProperty(
      'compound_has_default_callable',
      $component_data_info['compound_defaults']['properties']['compound_has_default_callable'],
      $component_data['compound_defaults'][0]
    );
    $this->assertArrayHasKey('compound_has_default_callable', $component_data['compound_defaults'][0],
      "The  were set in the component data info.");
    $this->assertEquals('compound_has_default_fixed_default_value_called', $component_data['compound_defaults'][0]['compound_has_default_callable'],
      "The options were set in the component data info.");

    // Create a 2nd child to check this works.
    $component_data['compound_defaults'][1] = [];
    $component_property_preparer->prepareComponentDataProperty(
      'compound_has_default_fixed',
      $component_data_info['compound_defaults']['properties']['compound_has_default_fixed'],
      $component_data['compound_defaults'][1]
    );
    $this->assertArrayHasKey('compound_has_default_fixed', $component_data['compound_defaults'][1],
      "The fixed default value was set in the component data child item.");
    $this->assertEquals('compound_has_default_fixed_default_value', $component_data['compound_defaults'][1]['compound_has_default_fixed'],
      "The fixed default value was set in the component data child item.");

    $component_property_preparer->prepareComponentDataProperty(
      'compound_has_default_callable',
      $component_data_info['compound_defaults']['properties']['compound_has_default_callable'],
      $component_data['compound_defaults'][1]
    );
    $this->assertArrayHasKey('compound_has_default_callable', $component_data['compound_defaults'][1],
      "The  were set in the component data info.");
    $this->assertEquals('compound_has_default_fixed_default_value_called', $component_data['compound_defaults'][1]['compound_has_default_callable'],
      "The options were set in the component data info.");

    // Component data info options get filled out.
    // Simple properties.
    $component_property_preparer->prepareComponentDataProperty('has_options', $component_data_info['has_options'], $component_data);
    $this->assertArraySubset(['A' => 'option_a'], $component_data_info['has_options']['options'],
      "The options were set in the component data info.");
    $this->assertArraySubset(['B' => 'option_b'], $component_data_info['has_options']['options'],
      "The options were set in the component data info.");

    // Presets.
    $component_property_preparer->prepareComponentDataProperty('has_presets', $component_data_info['has_presets'], $component_data);
    $this->assertArraySubset(['A' => 'option_a'], $component_data_info['has_presets']['options']);
    $this->assertArraySubset(['B' => 'option_b'], $component_data_info['has_presets']['options']);

    // Compound properties.
    // If we're interested in options, we can prepare the compound property in
    // one go.
    $component_property_preparer->prepareComponentDataProperty('compound_options', $component_data_info['compound_options'], $component_data);
    $this->assertArraySubset(['A' => 'option_a'], $component_data_info['compound_options']['properties']['compound_has_options']['options'],
      "The options were set in the component data info.");
    $this->assertArraySubset(['B' => 'option_b'], $component_data_info['compound_options']['properties']['compound_has_options']['options'],
      "The options were set in the component data info.");
  }

}
