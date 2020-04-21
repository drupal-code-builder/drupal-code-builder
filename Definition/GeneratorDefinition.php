<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\PropertyDefinition;

// we need this because we want to be able to selectively upgrade some generator
// classes to have their own getPropertyDefinition() method.
class GeneratorDefinition {

  protected $generatorClass;

  public static function create(string $generator_type) :PropertyDefinition {
    // Argh, need to instantiate the class handler outside of the Generate
    // task... time for a proper service architecture?
    $class_handler = new \DrupalCodeBuilder\Task\Generate\ComponentClassHandler;

    $this->generator_class = $class_handler->getGeneratorClass($generator_type);

    return $this->generator_class::getPropertyDefinition();
  }

}
