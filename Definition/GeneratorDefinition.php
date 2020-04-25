<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\PropertyDefinition;

// we need this because we want to be able to selectively upgrade some generator
// classes to have their own getPropertyDefinition() method.
class GeneratorDefinition extends PropertyDefinition {

  /**
   * The component type.
   *
   * @var string
   */
  protected $componentType;

  /**
   * The full generator class for this definition.
   *
   * @var string
   */
  protected $generatorClass;

  /**
   * Constructor.
   *
   * @param string $generator_type
   *   The generator type.
   */
  public function __construct(string $generator_type) {
    parent::__construct('complex');

    $class_handler = new \DrupalCodeBuilder\Task\Generate\ComponentClassHandler;

    $this->componentType = $generator_type;
    $this->generatorClass = $class_handler->getGeneratorClass($generator_type);
  }

  /**
   * Creates a new definition from a component type.
   *
   * @param string $generator_type
   *   The generator type; that is, the short class name without the version
   *   number.
   *
   * @return self
   *   The new definition.
   */
  static public function createFromGeneratorType(string $generator_type): self {
    return new static($generator_type);
  }

  public function getComponentType() :string {
    return $this->componentType;
  }

}
