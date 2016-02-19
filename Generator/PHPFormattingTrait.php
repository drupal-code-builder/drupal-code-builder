<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\PHPFormattingTrait.
 */

namespace ModuleBuilder\Generator;

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
   *  A string of docblock with start and end PHP comment markers. There is no
   *  trailing newline.
   */
  function docBlock($lines) {
    if (!is_array($lines)) {
      $lines = array($lines);
    }

    $lines = array_merge(
      array("/**"),
      array_map(function ($line) {
        return " * $line";
      }, $lines),
      array(" */")
    );

    return implode("\n", $lines);
  }

}
