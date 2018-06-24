<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Abstract Generator for root components.
 *
 * Root components are those with which the generating process may begin, such
 * as Module and Theme.
 */
abstract class RootComponent extends BaseGenerator {

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
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $component_data_definition = parent::componentDataDefinition();

    // Define this here for completeness; child classes should specialize it.
    $component_data_definition['root_name'] = [
      'label' => 'Extension machine name',
      'required' => TRUE,
      // Requested components will acquire the root name as the
      // 'root_component_name' property.
      'acquired_alias' => 'root_component_name',
    ];

    // Remove the root_component_name property that's come from the parent
    // class.
    unset($component_data_definition['root_component_name']);

    // Override the component_base_path property to be computed rather than
    // inherited.
    $component_data_definition['component_base_path'] = [
      'computed' => TRUE,
      'default' => function($component_data) {
        return '';
      },
    ];

    return $component_data_definition;
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
    return array();
  }

}
