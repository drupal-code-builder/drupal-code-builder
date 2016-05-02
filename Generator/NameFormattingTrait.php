<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\NameFormattingTrait.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Trait for formatting names.
 */
trait NameFormattingTrait {

  /**
   * Helper to convert a snake case string to camel case.
   *
   * @param $snake_case_string
   *  A string in the format 'convert_this'.
   *
   * @return
   *  The converted string, e.g. 'ConvertThis'.
   */
  function toCamel($snake_case_string) {
    // TODO: support split on '.' if needed?
    $pieces = explode('_', $snake_case_string);

    $camel = implode('', array_map('ucfirst', $pieces));

    return $camel;
  }

}
