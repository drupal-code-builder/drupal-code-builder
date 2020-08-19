<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Defines a data property that has an associated generator.
 */
class GeneratorDefinition extends PropertyDefinition {

  /**
   * The component type.
   *
   * @var string
   */
  protected $componentType;

  /**
   * Constructor.
   *
   * @param string $data_type
   *   The data type.
   * @param string $generator_type
   *   The generator type.
   */
  public function __construct(string $data_type, string $generator_type) {
    parent::__construct($data_type);

    $this->componentType = $generator_type;
  }

  /**
   * Creates a new definition from a component type.
   *
   * @param string $generator_type
   *   The generator type; that is, the short class name without the version
   *   number.
   * @param string $data_type
   *   (optional) The data type. Defaults to 'complex'.
   *
   * @return self
   *   The new definition.
   */
  static public function createFromGeneratorType(string $generator_type, string $data_type = 'complex'): self {
    return new static($data_type, $generator_type);
  }

  // we need a separate method for this because TESTS definitely don't want to
  // go adding the converted array defs.
  // also so we can remove this functionality easily in future
  static public function createFromGeneratorTypeWithConversion(string $generator_type, string $data_type = 'complex'): self {
    $definition = new static($data_type, $generator_type);

    $generate_task = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    // ARGH we're calling a method meant for the ROOT only!
    $array_property_info = $generate_task->getComponentDataInfo($generator_type, TRUE);

    \DrupalCodeBuilder\Generator\BaseGenerator::addArrayPropertyInfoToDefinition($definition, $array_property_info);

    return $definition;
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

}
