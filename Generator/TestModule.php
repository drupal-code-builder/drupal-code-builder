<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for a module that is used for testing.
 */
class TestModule extends Module {

  public static function baseComponentPropertyDefinitionAlter(PropertyDefinition $definition) {
    // Do nothing.
  }

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $component_data_definition = parent::componentDataDefinition();

    // Remove properties for components that test modules don't need.
    foreach ([
      'module_help_text',
      'api',
      'readme',
      'phpunit_tests',
      'tests',
    ] as $property) {
      unset($component_data_definition[$property]);
    }

    // Acuisition hack to work in the UI stage.
    $component_data_definition['test_class_name'] = PropertyDefinition::create('string')
      ->setInternal(TRUE)
      ->setExpressionDefault("get('..:..:..:plain_class_name')");

    $component_data_definition['root_name']->setDefault(
      DefaultDefinition::create()
        ->setExpression("classToMachine(get('..:test_class_name'))")
        ->setDependencies('..:test_class_name')
    );

    // The package is always 'Testing' for test modules, so set this to
    // computed.
    $component_data_definition['module_package']['default'] = 'Testing';
    $component_data_definition['module_package']['computed'] = TRUE;

    // Don't need this, but info file generators expect it.
    $component_data_definition['module_dependencies']['internal'] = TRUE;

    $component_data_definition['component_base_path']->getDefault()
      ->setLiteral('tests/modules/%module');

    return $component_data_definition;
  }

}
