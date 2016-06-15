<?php

/**
 * @file
 * Contains APIComponentDataPrepareTest.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/DrupalCodeBuilderTestBase.php';

/**
 * Tests the preparation step of component data.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/APIComponentDataPrepareTest.php
 * @endcode
 */
class APIComponentDataPrepareTest extends DrupalCodeBuilderTestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test defaults in component data.
   */
  function testComponentDataDefaults() {
    // A test array of component data info.
    $component_data_info = [
      'no_default_string' => [
        'label' => 'No default',
        'format' => 'string',
        'required' => FALSE,
      ],
      'no_default_array' => [
        'label' => 'No default',
        'format' => 'array',
        'required' => FALSE,
      ],
      'has_default_fixed' => [
        'label' => 'Module machine name',
        'default' => 'has_default_default',
        // We don't go through the system to fill in default info properties
        // here, so need them hardcoded.
        'required' => FALSE,
        'format' => 'string',
      ],
      'has_default_callable' => [
        'label' => 'Module machine name',
        'default' => function($component_data) {
          return $component_data['has_default_fixed'] . '_called';
        },
        'required' => FALSE,
        'format' => 'string',
      ],
      'has_options' => [
        'label' => 'Module machine name',
        'options' => function(&$property_info) {
          return [
            'A' => 'option_a',
            'B' => 'option_b',
          ];
        },
        'required' => FALSE,
        'format' => 'string',
      ],
      'compound' => [
        'label' => 'Compound properties',
        'properties' => [
          'compound_has_default_fixed' => [
            'label' => 'Module machine name',
            'default' => 'compound_has_default_fixed_default_value',
            // We don't go through the system to fill in default info properties
            // here, so need them hardcoded.
            'required' => FALSE,
            'format' => 'string',
          ],
          'compound_has_default_callable' => [
            'label' => 'Module machine name',
            'default' => function($component_data) {
              return $component_data['compound_has_default_fixed'] . '_called';
            },
            'required' => FALSE,
            'format' => 'string',
          ],
          'compound_has_options' => [
            'label' => 'Module machine name',
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

    // The $component_type parameter passed in won't affect the call to
    // prepareComponentDataProperty(), so it's fine to say 'module'.
    $mb_task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');

    $component_data = [];

    // Component data gets default values set if empty.
    // Simple properties.
    // Properties with no default still get something set into the data array.
    $mb_task_handler_generate->prepareComponentDataProperty('no_default_string', $component_data_info['no_default_string'], $component_data);
    $this->assertArrayHasKey('no_default_string', $component_data,
      "The empty string default value was set in the component data.");
    $this->assertEquals($component_data['no_default_string'], '',
      "The fixed default value was set in the component data.");

    $mb_task_handler_generate->prepareComponentDataProperty('no_default_array', $component_data_info['no_default_array'], $component_data);
    $this->assertArrayHasKey('no_default_array', $component_data,
      "The empty array default value was set in the component data.");
    $this->assertEquals($component_data['no_default_array'], [],
      "The fixed default value was set in the component data.");

    // Properties with an actual default value.
    $mb_task_handler_generate->prepareComponentDataProperty('has_default_fixed', $component_data_info['has_default_fixed'], $component_data);
    $this->assertArrayHasKey('has_default_fixed', $component_data,
      "The fixed default value was set in the component data.");
    $this->assertEquals($component_data['has_default_fixed'], 'has_default_default',
      "The fixed default value was set in the component data.");

    $mb_task_handler_generate->prepareComponentDataProperty('has_default_callable', $component_data_info['has_default_callable'], $component_data);
    $this->assertArrayHasKey('has_default_callable', $component_data,
      "The default value from a callable was set in the component data.");
    $this->assertEquals($component_data['has_default_callable'], 'has_default_default_called',
      "The default value from a callable was set in the component data.");

    // Compound properties.
    // TODO: these don't get set yet!

    // Component data info options get filled out.
    // Simple properties.
    $mb_task_handler_generate->prepareComponentDataProperty('has_options', $component_data_info['has_options'], $component_data);
    $this->assertArrayHasKey('A', $component_data_info['has_options']['options'],
      "The options were set in the component data info.");
    $this->assertArrayHasKey('B', $component_data_info['has_options']['options'],
      "The options were set in the component data info.");

    // Compound properties.
    $mb_task_handler_generate->prepareComponentDataProperty('compound', $component_data_info['compound'], $component_data);
    $this->assertArrayHasKey('A', $component_data_info['compound']['properties']['compound_has_options']['options'],
      "The options were set in the component data info.");
    $this->assertArrayHasKey('B', $component_data_info['compound']['properties']['compound_has_options']['options'],
      "The options were set in the component data info.");
  }

}
