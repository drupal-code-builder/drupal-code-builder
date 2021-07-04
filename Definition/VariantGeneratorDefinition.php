<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\VariantDefinition;
use MutableTypedData\Exception\InvalidDefinitionException;

/**
 * Allows a variant definition to define a generator.
 *
 * This means that the generator class that is referenced in the parent data
 * is basically just a dummy, and the real classes, defined with the variants,
 * are the ones that get actually instantiated as components.
 *
 * Variants gets their properties from the associated generator's
 * getPropertyDefinition() method.
 */
class VariantGeneratorDefinition extends VariantDefinition {

  /**
   * The component type.
   *
   * @var string
   */
  protected $componentType;

  /**
   * Sets the generator type for this variant.
   *
   * @param string $generator_type
   *   The generator type: the short class name of a Generator class.
   *
   * @return static
   */
  public function setGenerator(string $generator_type): self {
    $this->componentType = $generator_type;

    return $this;
  }

  /**
   * Gets the generator type for this variant.
   *
   * @return string
   *   The generator type.
   */
  public function getComponentType(): string {
    return $this->componentType;
  }

  /**
   * Gets the properties for this variant.
   *
   * @return array
   *   The array of property definitions.
   *
   * @throws \MutableTypedData\Exception\InvalidDefinitionException
   *   Throws an exception if the component type has not yet been set.
   */
  public function getProperties(): array {
    if (empty($this->componentType)) {
      throw new InvalidDefinitionException("Call to getProperties() when no component type has been set.");
    }

    $class_handler = \DrupalCodeBuilder\Factory::getTask('Generate\ComponentClassHandler');
    $definition = $class_handler->getStandaloneComponentPropertyDefinition($this->componentType);

    return $definition->getProperties();
  }


}
