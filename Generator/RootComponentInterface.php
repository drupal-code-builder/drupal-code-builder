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
   * RootComponent::getPropertyDefinition(), as child properties of a
   * 'configuration' complex property.
   *
   * @return \DrupalCodeBuilder\Definition\PropertyDefinition
   *   The data definition.
   */
  public static function configurationDefinition(): PropertyDefinition;

  /**
   * Alter the definition.
   *
   * This is mostly to allow easy skipping of this by TestModule.
   *
   * @param \DrupalCodeBuilder\Definition\PropertyDefinition $definition
   *   The definition from this class.
   */
  public static function rootComponentPropertyDefinitionAlter(PropertyDefinition $definition): void;

  /**
   * Provides replacement strings for tokens in code body.
   *
   * @return
   *  An array of tokens to replacements, suitable for use by strtr().
   */
  function getReplacements();

}