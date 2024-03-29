<?php

namespace DrupalCodeBuilder\Test\Fixtures\Task;

use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Task\Generate\ComponentClassHandler;

/**
 * Test class handler which returns generator classes from a fixture namespace.
 *
 * This is meant to work with a set of generator classes in the same namespace.
 *
 * TODO: Clean up this class!
 */
class TestComponentClassHandler extends ComponentClassHandler {

  protected $map = [];

  /**
   * Constructor.
   *
   * @param string $fixtureGeneratorNamespace
   *   The namespace within \DrupalCodeBuilder\Test\Fixtures in which to look
   *   for generator classes.
   * @param bool $useFallbackClass
   *   Whether to return SimpleGenerator when no class is found.
   */
  public function __construct(
    protected string $fixtureGeneratorNamespace,
    protected bool $useFallbackClass = FALSE,
  ) {
  }

  // YAGNI?
  public function setClassMap(array $map) {
    $this->map = $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getGeneratorClass($type) {
    $short_class_name = ucfirst($type);

    // Return generators in the fixtures namespace.
    $class_name = "\DrupalCodeBuilder\Test\Fixtures\\{$this->fixtureGeneratorNamespace}\\{$short_class_name}";
    if (class_exists($class_name)) {
      $class_name = $class_name;
      return $class_name;
    }

    if ($this->useFallbackClass) {
      return \DrupalCodeBuilder\Test\Fixtures\Generator\SimpleGenerator::class;
    }

    throw new \LogicException("No class '$class_name' found for '$type' in fixture namespace.");
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
    return MergingGeneratorDefinition::createFromGeneratorType($component_type, 'complex')
      ->setProperties([
        'primary' => PropertyDefinition::create('string'),
      ]);
  }

}
