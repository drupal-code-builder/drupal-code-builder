<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Defines a data property that has an associated generator.
 *
 * The data definition from the generator is not merged into this definition.
 * This is used for simple properties for which a generator component is
 * needed. The component is instantiated as standalone data during component
 * collection.
 *
 * For example, API, Readme, ServiceProvider, where a boolean property on the
 * host data suffices, but a generator is used to create the code.
 */
class DeferredGeneratorDefinition extends PropertyDefinition {

  /**
   * Constructor.
   *
   * @param string $data_type NO!
   *   The data type.
   * @param string $generator_type
   *   The generator type.
   */
  public function __construct(
    string $data_type,
    protected string $componentType,
    protected string $generatorClass,
  ) {
    parent::__construct($data_type);
  }

  /**
   * Creates a new definition from a component type.
   *
   * @param string $generator_type
   *   The generator type; that is, the short class name without the version
   *   number.
   * @param string $data_type
   *   The data type to use for the simple property on the host definition.
   *
   * @return static
   *   The new definition.
   */
  static public function createFromGeneratorType(string $generator_type, string $data_type): PropertyDefinition {
    $class_handler = \DrupalCodeBuilder\Factory::getContainer()->get('Generate\ComponentClassHandler');
    $generator_class = $class_handler->getGeneratorClass($generator_type);

    return new static($data_type, $generator_type, $generator_class);
  }

  /**
   * Gets the component type for this property.
   *
   * @return string
   *   The component type.
   */
  public function getComponentType() :string {
    return $this->componentType;
  }

}
