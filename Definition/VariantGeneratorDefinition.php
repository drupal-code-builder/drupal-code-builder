<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\DataDefinition;
use MutableTypedData\Definition\PropertyListInterface;
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
 * addToGeneratorDefinition() method.
 */
class VariantGeneratorDefinition extends VariantDefinition implements PropertyListInterface {

  use PropertyManipulationTrait;

  /**
   * Whether properties have been obtained from the generator class yet.
   *
   * @var bool
   */
  protected bool $generatorPropertiesLoaded = FALSE;

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
   * {@inheritdoc}
   */
  public function addProperty(DataDefinition $property): self {
    if (empty($property->getName())) {
      throw new InvalidDefinitionException("Properties added with addProperty() must have a machine name set.");
    }

    $this->properties[$property->getName()] = $property;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperty(string $name): DataDefinition {
    if (!isset($this->properties[$name])) {
      throw new \Exception(sprintf("Property definition '%s' has no child property '$name' defined.",
        $this->name,
        $name
      ));
    }

    return $this->properties[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties(): array {
    if (empty($this->componentType)) {
      throw new InvalidDefinitionException("Call to getProperties() when no component type has been set.");
    }

    if (!$this->generatorPropertiesLoaded) {
      // Set this to TRUE now to avoid recursion, as addToGeneratorDefinition()
      // may need access to properties.
      $this->generatorPropertiesLoaded = TRUE;

      $class_handler = \DrupalCodeBuilder\Factory::getTask('Generate\ComponentClassHandler');
      $generator_class = $class_handler->getGeneratorClass($this->componentType);

      // Allow the generator class to add or change properties.
      $generator_class::addToGeneratorDefinition($this);
    }

    return parent::getProperties();
  }

}
