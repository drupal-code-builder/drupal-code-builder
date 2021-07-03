<?php

namespace DrupalCodeBuilder\Test\Fixtures\Task;

use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Task\Generate\ComponentClassHandler;

/**
 *  Task TODO helper for working with generator classes and instantiating them.
 */
class TestComponentClassHandler extends ComponentClassHandler {

  protected $map = [];

  // YAGNI?
  public function setClassMap(array $map) {
    $this->map = $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getGeneratorClass($type) {
    // Return generators in the fixtures namespace.
    if (class_exists('\DrupalCodeBuilder\Test\Fixtures\Generator\\' . $type)) {
      $class_name = '\DrupalCodeBuilder\Test\Fixtures\Generator\\' . $type;
      return $class_name;
    }

    throw new \LogicException("No class found for '$type' in fixture namespace.");
  }

  /**
   * TODO
   */
  public function getGenerator($component_type, $component_data = NULL) {
    // TODO: Special case, probably needs fixing, unifying with
    // getGeneratorClass()/
    // Return the SimpleGenerator.
    if (!isset($this->map[$component_type])) {
      $generator = new \DrupalCodeBuilder\Test\Fixtures\Generator\SimpleGenerator();
      $generator->componentType = $component_type;
    }
    return $generator;
  }

  public function getStandaloneComponentPropertyDefinition($component_type, $machine_name = NULL): PropertyDefinition {
    // ARGH too test-specific!
    return GeneratorDefinition::createFromGeneratorType($component_type, 'complex')
      ->setProperties([
        'primary' => PropertyDefinition::create('string'),
      ]);
  }

}
