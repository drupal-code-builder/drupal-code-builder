<?php

namespace DrupalCodeBuilder\Generator\Render;

use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;

/**
 * Renderer for fluent method calls which is itself fluent.
 *
 * This allows code for fluent calls to be created in most cases by simply
 * copy-pasting the code that is to be output. The design goal here is that
 * you should be able to look at a sample of the code that you want to
 * generate, and as near as possible copy it into a generator class. This
 * makes coding and maintenance easier.
 *
 * Each call to the FluentMethodCall adds a fluent call to the render output,
 * with the name of the method called. Final output is obtained by calling
 * getCodeLines(). For example:
 * @code
 * $fluent_call = new FluentMethodCall;
 * $fluent_call->foo('value')
 *   ->bar('othervalue');
 * @endcode
 *
 * will actually produce these lines from getCodeLines():
 *
 * @code
 *   ->foo('value')
 *   ->bar('othervalue');
 * @endcode
 *
 * TODO: Once this renderer matures, replace the earlier attempts.
 */
class FluentMethodCall {

  use PHPFormattingTrait;

  /**
   * The rendered lines of code.
   *
   * @var string[]
   */
  protected $lines = [];

  /**
   * Magic method: adds a method call to this object as a call to be rendered.
   */
  public function __call($method_name, $parameters) {
    $fluent_call_line = "->{$method_name}(";
    foreach ($parameters as $index => $parameter) {
      // Do checking whether this is a non-string first, as substr() will
      // complain if it's checked first.
      if (is_callable($parameter)) {
        // If the parameter is a callable, then it's the return value of either
        // ::code or ::quote.
        $fluent_call_line .= $parameter();
      }
      elseif (is_array($parameter)) {
        // Hack! Abusing the FormAPI renderer as a general array renderer!
        $fluent_call_line .= '[';

        $array_renderer = new FormAPIArrayRenderer($parameter);

        // Cheat, and put this in the array of rendered lines now, as this case
        // needs to output more than one line.
        $this->lines[] = $fluent_call_line;

        $array_lines = $array_renderer->render();
        $array_lines = $this->indentCodeLines($array_lines);

        $this->lines = array_merge($this->lines, $array_lines);

        // Put the last line for the array parameter in the ongoing line.
        $fluent_call_line = ']';
      }
      // £ is unicode apparently.
      elseif (mb_substr($parameter, 0, 1) == '£') {
        $fluent_call_line .= $parameter;
      }
      elseif (substr($parameter, 0, 2) == 't(') {
        $fluent_call_line .= $parameter;
      }
      elseif (is_string($parameter)) {
        $fluent_call_line .= '"' . $parameter . '"';
      }
      elseif (is_bool($parameter)) {
        $fluent_call_line .= $parameter ? 'TRUE' : 'FALSE';
      }
      else {
        $fluent_call_line .= $parameter;
      }

      if ($index != count($parameters) - 1) {
        $fluent_call_line .= ', ';
      }
    }

    $fluent_call_line .= ')';

    $this->lines[] = $fluent_call_line;

    return $this;
  }

  /**
   * Completes rendering and returns the code lines.
   *
   * @return string[]
   *   The code lines, indented and with a final ';'.
   */
  public function getCodeLines() {
    // Add a terminal ';' to the last of the fluent method calls.
    $this->lines[count($this->lines) - 1] .= ';';

    $this->lines = $this->indentCodeLines($this->lines);

    return $this->lines;
  }

  /**
   * Treats the given string as the actual code to output.
   *
   * @param $string
   *   The string of code.
   *
   * @return callable
   *   An anonymous function that will return the given code string.
   */
  static public function code($string) {
    return function () use ($string) {
      return $string;
    };
  }

  /**
   * Treats the given string as a value to be quoted.
   *
   * @param $string
   *   The string value.
   *
   * @return callable
   *   An anonymous function that will return the given string, quoted.
   */
  static public function quote($string) {
    return function () use ($string) {
      return '"' . $string . '"';
    };
  }

  /**
   * Treats the given string as a value to be translated.
   *
   * @param $string
   *   The string value.
   *
   * @return callable
   *   An anonymous function that will return the given string, wrapped in a
   *   call to t().
   */
  static public function t($string) {
    return function () use ($string) {
      return 't("' . $string . '")';
    };
  }

}
