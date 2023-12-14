<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Interface for definitions which have an associated generator class.
 */
interface GeneratorDefinitionInterface {

  /**
   * Gets the component type for this property.
   *
   * @return string
   *   The component type.
   */
  public function getComponentType(): string;

}