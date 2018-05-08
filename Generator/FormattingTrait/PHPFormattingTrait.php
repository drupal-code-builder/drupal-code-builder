<?php

namespace DrupalCodeBuilder\Generator\FormattingTrait;

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
   * Indent all the non-empty lines in a block of code.
   *
   * @param array $lines
   *   An array of code lines.
   * @param int $indent
   *   (optional) The number of indentation levels to add. Defaults to 1, that
   *   is, an indentation of two spaces.
   *
   * @return
   *   The array of code lines with the indentation applied.
   */
  function indentCodeLines($lines, $indent = 1) {
    $indent = str_repeat('  ', $indent);

    $indented_lines = array_map(function ($line) use ($indent) {
      return empty($line) ? $line : $indent . $line;
    }, $lines);
    return $indented_lines;
  }

}
