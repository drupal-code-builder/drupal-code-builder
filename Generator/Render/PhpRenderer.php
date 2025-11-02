<?php

namespace DrupalCodeBuilder\Generator\Render;

/**
 * Abstract base class for PHP renderers.
 */
abstract class PhpRenderer {

  /**
   * Renders a scalar or NULL value as a PHP string.
   *
   * To render:
   *  - A class or an expression starting with a class such as a class constant
   *    or the special '::class' expression, pass the fully-qualified classname
   *    or class constant as a string starting with '\\'.
   *  - A variable, pass the variable name as a string starting with '£' instead
   *    of '$'.
   *  - A boolean, pass either a literal boolean or a string such as 'TRUE'.
   *  - NULL, either the NULL value or the string 'NULL'.
   *  - A numeric value, either the value or the quoted value.
   *
   * @param mixed $value
   *   The value to render.
   *
   * @return string
   *   A string of PHP code representing the value.
   */
  protected function renderScalar(mixed $value): string {
    // Handle natives which are represented as strings.
    if (in_array($value, ['TRUE', 'FALSE', 'NULL'], TRUE)) {
      return $value;
    }

    if (is_string($value)) {
      $unquoted_string =
        // Special case for class constants: we assume a string starting with a
        // '\' is such and thus is not quoted.
        str_starts_with($value, '\\')
        // A string starting with £ will get replaced as a variable and should
        // not be quoted.
        || str_starts_with($value, '£')
        // An array should not be quoted and probably shouldn't be passed as a
        // string but this is here for BC.
        || str_starts_with($value, '[');

      if ($unquoted_string) {
        $value_string = $value;
      }
      else {
        $value_string = $this->quoteString($value);
      }
    }
    elseif (is_numeric($value)) {
      $value_string = (string) $value;
    }
    elseif (is_bool($value)) {
      $value_string = $value ? 'TRUE' : 'FALSE';
    }
    elseif (is_null($value)) {
      return 'NULL';
    }
    else {
      dump($value);
      throw new \Exception("Scalar value not handled!");
    }

    return $value_string;
  }

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
