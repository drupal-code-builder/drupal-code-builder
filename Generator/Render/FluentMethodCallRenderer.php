<?php

namespace DrupalCodeBuilder\Generator\Render;

use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;

/**
 * Renderer for fluent method calls.
 */
class FluentMethodCallRenderer {

  use PHPFormattingTrait;

  /**
   * The original data array.
   *
   * @var array
   */
  protected $data;

  /**
   * Creates a new FormAPIArrayRenderer.
   *
   * @param array $data
   *   An array of data for a series of fluent calls. Each key is the name of
   *   the method to call. Multiple calls to the same method can be handled
   *   by appending a dummy suffix of '__FOO' to the method name, where the
   *   'FOO' is arbitrary. Values are either a single parameter, or an array of
   *   parameters where:
   *    - A string starting with '£' is taken as a variable and used verbatim.
   *    - A string starting with 't(' is taken as a translated string and used
   *      verbatin.
   *    - Any other string will be quoted as a string parameter.
   *    - A boolean is rendered as the boolean constant, e.g. the string 'TRUE'.
   *    - And instance of DrupalCodeBuilder\Generator\Render\FormAPIArrayRenderer
   *      is taken as an array of data, and rendered by that as indented lines.
   */
  public function __construct($data) {
    $this->data = $data;
  }

  /**
   * Creates the rendered lines.
   *
   * @return array
   *   The array of rendered lines.
   */
  public function render() {
    $render = [];

    if (empty($this->data)) {
      return [];
    }

    foreach ($this->data as $method_name => $parameters) {
      if (!is_array($parameters)) {
        $parameters = [$parameters];
      }

      if ($position = strpos($method_name, '__') !== FALSE) {
        $method_name = substr($method_name, 0, strpos($method_name, '__'));
      }

      $fluent_call_line = "->{$method_name}(";
      foreach ($parameters as $index => $parameter) {
        // Hack! Abusing the FormAPI renderer as a general array renderer!
        if ($parameter instanceof \DrupalCodeBuilder\Generator\Render\FormAPIArrayRenderer) {
          // Do object checking first, as substr() will complain if it's checked
          // first.
          $fluent_call_line .= '[';

          // Cheat, and put this in the array of rendered lines now.
          $render[] = $fluent_call_line;

          $array_lines = $parameter->render();
          $array_lines = $this->indentCodeLines($array_lines);
          $render = array_merge($render, $array_lines);


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

      $render[] = $fluent_call_line;
    }

    // Add a terminal ';' to the last of the fluent method calls.
    $render[count($render) - 1] .= ';';

    $render = $this->indentCodeLines($render);

    return $render;
  }

}
