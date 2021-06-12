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
   * componentDataDefinition(), as child properties of a 'configuration' complex
   * property.
   *
   * @return \DrupalCodeBuilder\Definition\PropertyDefinition
   *   The data definition.
   */
  public static function configurationDefinition(): PropertyDefinition {
    // Return an empty data definition by default.
    // NOTE: this can't have a root name set because it's also embedded into
    // data by self::componentDataDefinition().
    return PropertyDefinition::create('complex');
  }

  /**
   * Implements DefinitionProviderInterface's method.
   *
   * We need the base PropertyDefinition here for the interface compatibility.
   */
  public static function getDefinition(): BasePropertyDefinition {
    return static::getPropertyDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $component_data_definition = parent::componentDataDefinition();

    // Define this here for completeness; child classes should specialize it.
    $component_data_definition['root_name'] = PropertyDefinition::create('string')
      ->setLabel('Extension machine name')
      ->setRequired(TRUE);

    // Remove the root_component_name property that's come from the parent
    // class.
    unset($component_data_definition['root_component_name']);

    // Override the component_base_path property to be computed rather than
    // inherited.
    $component_data_definition['component_base_path'] = PropertyDefinition::create('string')
      ->setInternal(TRUE)
      ->setDefault(
        DefaultDefinition::create()
          ->setLiteral('')
      );

      // Add the configuration data definition as internal.
      $component_data_definition['configuration'] = static::configurationDefinition()
        ->setInternal(TRUE);

    return $component_data_definition;
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
