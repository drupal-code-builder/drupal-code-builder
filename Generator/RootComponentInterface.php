<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefinitionProviderInterface;

interface RootComponentInterface extends GeneratorInterface, DefinitionProviderInterface {

  /**
   * Defines the data for this root component's configuration.
   *
   * Configuration data is intended to be used by UIs to allow users to store
   * their preferences in a persistent fashion.
   *
   * The properties here are merged into the main data by
   * RootComponent::addToGeneratorDefinition(), as child properties of a
   * 'configuration' complex property.
   *
   * @return \DrupalCodeBuilder\Definition\PropertyDefinition
   *   The data definition.
   */
  public static function configurationDefinition(): PropertyDefinition;

  /**
   * Provides replacement strings for tokens in code body.
   *
   * @return
   *  An array of tokens to replacements, suitable for use by strtr().
   */
  function getReplacements();

}
