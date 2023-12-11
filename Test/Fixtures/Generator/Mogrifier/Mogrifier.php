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
  public static function addToGeneratorDefinition($definition) {
    // Omit the parent call so we don't get a ton of base properties.

    $definition->addProperties([
      'string_property' => PropertyDefinition::create('string'),
      'boolean_generator_property' => GeneratorDefinition::createFromGeneratorType('MogrifierBooleanComponent')
        ->setLabel("Boolean Generator"),
      'complex_generator_property' => GeneratorDefinition::createFromGeneratorType('MogrifierComplexComponent')
        ->setLabel("Compound Generator")
        ->setMultiple(TRUE),
      'mutable_generator_property' => GeneratorDefinition::createFromGeneratorType('MogrifierMutableComponent')
        ->setLabel("Mutable Generator")
        ->setMultiple(TRUE),
    ]);
  }

}

///////////////////////////////
__halt_compiler();


/// SIMPLE - no lazy

Generate
  - get component data
  - resolve 'module' to Module class
  - do createFromProvider(Module)

.. Data item factory, gets provider class.
  - do getDefinition()

Module, Root, implements DefinitionProviderInterface::getDefinition()
  - build Definition, add properties
    - do GeneratorDefinition::createFromGeneratorType('MogrifierCompoundComponent')
      - add label, multiple.

GeneratorDefinition::createFromGeneratorType
  - resolve class from generator type
  - call Generator class::getDefinition

MogrifierCompoundComponent::getDefinition -- same as on root! Is that ok?
  - build Definition, add properties


/// With lazy!

Generate
  - get component data
  - resolve 'module' to Module class
  - do createFromProvider(Module)

.. Data item factory, gets provider class.
  - do getDefinition()

Module, Root, implement DefinitionProviderInterface::getDefinition()
  - build GeneratorDefinition('CompoundGenerator') - WHO DOES THIS?
    we need to get stuff from CompoundGenerator!
  - add name
  - add label ???????
  - lazy load properties NON RECURSIVE! since we go back into getDefinition anyway!

1. GeneratorDefinition::createFromGeneratorType

2. lazy load of properties
  GeneratorDefinition::getProperties()
  - resolve class from generator type
  - call Module::getProperties()

Module::getProperties
  - do GeneratorDefinition::createFromGeneratorType('MogrifierCompoundComponent')
    - add label, multiple.

GeneratorDefinition::createFromGeneratorType
  - resolve class from generator type
  - call Generator class::getDefinition

MogrifierCompoundComponent::getDefinition -- same as on root! Is that ok? NO
   use different, for label/name reasons.
  - build Definition

