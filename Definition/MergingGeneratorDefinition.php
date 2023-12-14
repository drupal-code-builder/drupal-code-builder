<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Defines a data property from a generator.
 *
 * The data definition from the generator is merged into this definition
 * object.
 *
 * This gets the data type from the generator class, and lazily allows the
 * geneator class to add to the definition.
 *
 * The laziness is so that generator classes can be used repeatedly and take
 * care of removing properties from a parent class that would cause recursion:
 * for example the Module and TestModule generators.
 */
class MergingGeneratorDefinition extends PropertyDefinition implements GeneratorDefinitionInterface {

  /**
   * Whether properties have been obtained from the generator class yet.
   *
   * @var bool
   */
  protected bool $generatorPropertiesLoaded = FALSE;

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
   *
   * @return static
   *   The new definition.
   */
  static public function createFromGeneratorType(string $generator_type): PropertyDefinition {
    $class_handler = \DrupalCodeBuilder\Factory::getContainer()->get('Generate\ComponentClassHandler');
    $generator_class = $class_handler->getGeneratorClass($generator_type);
    $data_type = $generator_class::getDefinitionDataType();

    return new static($data_type, $generator_type, $generator_class);
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentType(): string {
    // TODO: Handle mutable data and switch the component class here!
    return $this->componentType;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    if (!$this->generatorPropertiesLoaded) {
      // Set this to TRUE now to avoid recursion, as addToGeneratorDefinition()
      // may need access to properties.
      $this->generatorPropertiesLoaded = TRUE;

      // Allow the generator class to add or change properties.
      $this->generatorClass::addToGeneratorDefinition($this);
    }

    return $this->properties;
  }

}
