<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\DataDefinition as BasePropertyDefinition;
use MutableTypedData\Definition\DefinitionProviderInterface;

/**
 * Abstract Generator for root components.
 *
 * Root components are those with which the generating process may begin, such
 * as Module and Theme.
 *
 * These are used by
 * \DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory to
 * instantiate data objects.
 */
abstract class RootComponent extends BaseGenerator implements DefinitionProviderInterface {

  /**
   * The sanity level this generator requires to operate.
   */
  protected static $sanity_level = 'none';

  /**
   * Returns this generator's sanity level.
   *
   * @return string
   *  The sanity level name.
   */
  public static function getSanityLevel() {
    return static::$sanity_level;
  }

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
  public static function configurationDefinition(): PropertyDefinition {
    // Return an empty data definition by default.
    // NOTE: this can't have a root name set because it's also embedded into
    // data by self::getPropertyDefinition().
    return PropertyDefinition::create('complex');
  }

  /**
   * Implements DefinitionProviderInterface's method.
   *
   * We need the base PropertyDefinition here for the interface compatibility.
   */
  public static function getDefinition(): BasePropertyDefinition {
    $definition = static::getGeneratorDataDefinition();

    // Load all the lazy properties now we have the complete definition.
    $definition->loadLazyProperties();

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function setProperties(PropertyDefinition $definition): void {
    parent::setProperties($definition);

    static::rootComponentPropertyDefinitionAlter($definition);
  }

  /**
   * Alter the definition.
   *
   * This is mostly to allow easy skipping of this by TestModule.
   *
   * @param \DrupalCodeBuilder\Definition\PropertyDefinition $definition
   *   The definition from this class.
   */
  abstract public static function rootComponentPropertyDefinitionAlter(PropertyDefinition $definition): void;

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    // Define this here for completeness; child classes should specialize it.
    $definition->addProperties([
      'root_name' => PropertyDefinition::create('string')
      ->setLabel('Extension machine name')
      ->setRequired(TRUE),
    ]);

    // Remove the root_component_name property that's come from the parent
    // class.
    $definition->removeProperty('root_component_name');

    // Override the component_base_path property to be computed rather than
    // inherited.
    $definition->addProperties([
      'component_base_path' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setLiteral('')
        ),
      // Add the configuration data definition as internal.
      'configuration' => static::configurationDefinition()
        ->setInternal(TRUE),
    ]);

    return $definition;
  }

  public function isRootComponent(): bool {
    return TRUE;
  }

  /**
   * Filter the file info array to just the requested build list.
   *
   * @param &$files
   *  The array of built file info.
   * @param $build_list
   *  The build list parameter from the original Generate component data.
   * @param $component_data
   *  The original component data.
   */
  public function applyBuildListFilter(&$files, $build_list, $component_data) {
  }

  /**
   * Provides replacement strings for tokens in code body.
   *
   * @return
   *  An array of tokens to replacements, suitable for use by strtr().
   */
  function getReplacements() {
    // Root components should override this.
    return [];
  }

}
