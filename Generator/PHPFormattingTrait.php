<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\PHPFormattingTrait.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Trait for PHP formatting.
 */
trait PHPFormattingTrait {

  /**
   * Helper to format text as docblock.
   *
   * @param @lines
   *  An array of lines, or a single line of text. Lines to be normally indented
   *  should have no leading whitespace.
   *
   * @return
   *  An array of lines for the docblock with start and end PHP comment markers.
   */
  function docBlock($lines) {
    if (!is_array($lines)) {
      $lines = array($lines);
    }

    $lines = array_merge(
      array("/**"),
      array_map(function ($line) {
        if (empty($line)) {
          return ' *';
        }
        return " * $line";
      }, $lines),
      array(" */")
    );

    return $lines;
  }

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
