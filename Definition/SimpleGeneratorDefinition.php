<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Defines a data property that has an associated generator. TODO
 *
 * TODO Rename this - it's not about simple, it's about proxied - the property
 * in the parent is not the same as the generator's properties.
 */
class SimpleGeneratorDefinition extends GeneratorDefinition {

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
  static public function createFromGeneratorType(string $generator_type, string $data_type = NULL): PropertyDefinition {
    assert(!empty($data_type));

    $class_handler = \DrupalCodeBuilder\Factory::getContainer()->get('Generate\ComponentClassHandler');
    $generator_class = $class_handler->getGeneratorClass($generator_type);

    return new static($data_type, $generator_type, $generator_class);
  }

  public function getProperties() {
    return [];
  }

}