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
        // If the parameter is a callable, then it's the return value of
        // self::t().
        $fluent_call_line .= $parameter();
      }
      else {
        $fluent_call_line .= PhpValue::create($parameter)->renderInline();
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
