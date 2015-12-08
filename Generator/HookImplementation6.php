<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\HookImplementation6.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator class for hook implementations for Drupal 6.
 */
class HookImplementation6 extends HookImplementation {

  /**
   * Make the doxygen first line for a given hook with the Drupal 6 format.
   *
   * @param
   *   The long hook name, eg 'hook_menu'.
   */
  function hook_doxygen_text($hook_name) {
    return "Implementation of $hook_name().";
  }

}
