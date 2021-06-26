<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for a module that is used for testing.
 */
class TestModule extends Module {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    // Remove properties for components that test modules don't need.
    foreach ([
      'module_help_text',
      'api',
      'readme',
      // 'phpunit_tests',
      'tests',
    ] as $property) {
      $definition->removeProperty($property);
    }

    // Acuisition hack to work in the UI stage.
    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('test_class_name')
      ->setInternal(TRUE)
      ->setExpressionDefault("get('..:..:..:plain_class_name')")
    );

    $definition->getProperty('root_name')->setDefault(
      DefaultDefinition::create()
        ->setExpression("classToMachine(get('..:test_class_name'))")
        ->setDependencies('..:test_class_name')
    );

    // The package is always 'Testing' for test modules, so set this to
    // computed.
    $definition->getProperty('module_package')
      ->setLiteralDefault('Testing')
      ->setInternal(TRUE);

    // Don't need this, but info file generators expect it.
    $definition->getProperty('module_dependencies')
      ->setInternal(TRUE);

    $definition->getProperty('component_base_path')
      ->setLiteralDefault('tests/modules/%module');

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function rootComponentPropertyDefinitionAlter(PropertyDefinition $definition): void {
    // Do nothing.
  }

}
