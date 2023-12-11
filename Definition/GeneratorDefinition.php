<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Defines a data property that has an associated generator.
 *
 * This gets the data type from the generator class, and lazily allows the
 * geneator class to add to the definition.
 *
 * The laziness is so that generator classes can be used repeatedly and take
 * care of removing properties from a parent class that would cause recursion:
 * for example the Module and TestModule generators.
 */
class GeneratorDefinition extends PropertyDefinition {

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
   * @param string $data_type NO KILL, the GENERATOR tells us this.
   *   (optional) The data type. Defaults to 'complex'.
   *
   * @return static
   *   The new definition.
   */
  // Should this go on a factory class? this class isn't the one getting
  // instantiated!
  static public function createFromGeneratorType(string $generator_type): PropertyDefinition {
    $class_handler = \DrupalCodeBuilder\Factory::getContainer()->get('Generate\ComponentClassHandler');
    $generator_class = $class_handler->getGeneratorClass($generator_type);
    $data_type = $generator_class::getDefinitionDataType();

    return new static($data_type, $generator_type, $generator_class);
  }

  /**
   * Gets the component type for this property.
   *
   * @return string
   *   The component type.
   */
  public function getComponentType() :string {
    // TODO: Handle mutable data and switch the component class here!
    return $this->componentType;
  }

  public function getProperties() {
    if (empty($this->componentType)) {
      throw new InvalidDefinitionException("Call to getProperties() when no component type has been set.");
    }

    // Add the properties from the generator class.
    $this->generatorClass::addProperties($this);

    // Get the properties of all children to lazy load them.
    foreach ($this->properties as $property) {
      $property->getProperties();
    }

    return $this->properties;
  }

}
