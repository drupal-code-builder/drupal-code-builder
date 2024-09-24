<?php

namespace DrupalCodeBuilder\Generator\FormattingTrait;


/**
 * Trait for PHP formatting.
 */
trait PHPFormattingTrait {

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
  function indentCodeLines(array $lines, int $indent = 1) {
    $indent = str_repeat('  ', $indent);

    $indented_lines = array_map(function ($line) use ($indent) {
      return empty($line) ? $line : $indent . $line;
    }, $lines);
    return $indented_lines;
  }

}
