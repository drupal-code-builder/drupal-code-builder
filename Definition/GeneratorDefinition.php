<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\PropertyDefinition;

class GeneratorDefinition {

  protected $generatorClass;

  public static function create(string $generator_type) :PropertyDefinition {
    // Argh, need to instantiate the class handler outside of the Generate
    // task... time for a proper service architecture?
    $class_handler = new \DrupalCodeBuilder\Task\Generate\ComponentClassHandler;

    $generator_class = $class_handler->getGeneratorClass($generator_type);

    return $generator_class::getPropertyDefinition();
  }

}
