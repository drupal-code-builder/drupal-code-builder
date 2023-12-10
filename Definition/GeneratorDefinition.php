<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Defines a data property that has an associated generator.
 *
 * TODO: aaargh! Is this to be used in the parent generator, or the generator
 * itself?? There are examples of both usages!! Plugin and PluginType use in the
 * generator itself; BaseGenerator in its conversion shim code is the parent
 * using it to define the child item. So far, the correct thing is to use
 * BaseGenerator::getLazyDataDefinitionForGeneratorType() in the parent
 * generator case.
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
    protected string $componentType,
    protected string $generatorClass,
  ) {
    // $this->componentType = $generator_type;

    // // get $data_type from Generator.
    // // TEMP!
    $data_type = 'complex';

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

    // NO WAIT the $generator_class needs to say stuff like we're complex or mutable!

    return new static($generator_type, $generator_class);
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
    return $this->generatorClass::addProperties($this);
  }

}
