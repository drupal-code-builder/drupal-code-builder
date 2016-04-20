<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\RootComponent.
 */

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
  public static $sanity_level = 'none';


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
