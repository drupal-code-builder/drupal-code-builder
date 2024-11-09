<?php

namespace DrupalCodeBuilder\Generator\Render;

/**
 * Abstract base class for PHP renderers.
 */
abstract class PhpRenderer {

  /**
   * Quotes a string for use in PHP code.
   *
   * @param string $string
   *   The string to quote.
   *
   * @return string
   *   The string with quotes around it. Single quotes are used if possible.
   */
  protected function quoteString(string $string): string {
    if (!str_contains($string, "'")) {
      // Use single quotes if we can.
      return "'" . $string . "'";
    }
    elseif (!str_contains($string, '"')) {
      // Fall back to double quotes if there are single quotes in the string.
      return '"' . $string . '"';
    }
    else {
      // Finally, fall back to escaping everything.
      return "'" . addslashes($string) . "'";
    }
  }

}
