<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator\Mogrifier;

use DrupalCodeBuilder\Generator\RootComponent;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Dummy generator class for tests.
 *
 * This is the root component for a set of generators.
 *
 * Pathways:
 *  - plain compound - properties in the Generator
 *  - compound, recursive - need to lazily define properties to prevent recursion so a
 *    child class can unset them:
 *    Mogrifier -> Compound -> SubMogrifier
 *
 *  - mutable at the property level
 *  - boolean at the property level, but becomes a generator.
 */
class Mogrifier extends RootComponent {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'string_property' => PropertyDefinition::create('string'),

      //  this needs to be combined with the result of getDataDefinition.
      'generator_property' => GeneratorDefinition::createFromGeneratorType('CompoundGenerator')
        ->setLabel("Compound Generator")
        ->setMultiple(TRUE),
    ]);

    return $definition;
  }

  public static function rootComponentPropertyDefinitionAlter(PropertyDefinition $definition): void {
    // Does nothing.
  }

}

///////////////////////////////
__halt_compiler();

///

Generate
  - get component data
  - resolve 'module' to Module class
  - do createFromProvider(Module)

Module, Root, implement DefinitionProviderInterface::getDefinition()
  - build Definition, add properties
    - do GeneratorDefinition::createFromGeneratorType('MogrifierCompoundComponent')
      - add label, multiple.

GeneratorDefinition::createFromGeneratorType
  - resolve class from generator type
  - call Generator class::getDefinition

MogrifierCompoundComponent::getDefinition -- same as on root! Is that ok?
  - build Definition, add properties
