<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;

/**
 * Generator base class for functions.
 *
 * (We can't call this 'Function', as that's a reserved word.)
 */
class PHPFunction extends BaseGenerator {

  use PHPFormattingTrait;

  /**
   * The code file this function belongs in.
   *
   * This is in the form of a relative path to the module folder, with
   * placeholders such as '%module'.
   * TODO: add a @see to the function that does placeholder replacement.
   */
  protected $code_file;

  /**
   * Constructor.
   *
   * @param $component_name
   *  The name of a function component should be its function (or method) name.
   * @param $component_data
   *   An array of data for the component. Any missing properties are given
   *   default values. Valid properties are:
   *    - 'code_file': The name of the file component for the file that this
   *       function should be placed into.
   *    - 'doxygen_first': The text of the first line of doxygen.
   *    - 'declaration': The function declaration, including the function name
   *      and parameters, up to the closing parenthesis. Should not however
   *      include the opening brace of the function body.
   *    - 'body' The code of the function. The character '£' is replaced with
   *      '$' as a convenience to avoid having to keep escaping names of
   *      variables. This can be in one of the following forms:
   *      - a string, not including the enclosing function braces. The opening
   *        and closing newlines may be included: indicate this by setting
   *        'has_wrapping_newlines' to TRUE.
   *      - an array of lines of code. These should not have their newlines.
   *    - 'has_wrapping_newlines': (optional) If the 'body' is a string, this
   *      indicates whether the string has first and closing newlines.
   *    - 'body_indent': (optional) The number of spaces to add to the start of
   *      each line, if 'body' is an array. This is relative to the indentation
   *      of the function as a whole; that is, it does not need to be increased
   *      for a class method. Defaults to 2.
   */
  function __construct($component_name, $component_data, $root_generator) {
    // Set defaults.
    $component_data += array(
      // TODO: Remove, unused.
      'code_file' => '%module.module',
      'doxygen_first' => 'TODO: write function documentation.',
      'body_indent' => 2,
    );

    $this->code_file = $component_data['code_file'];

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    // Trim newlines from start and end of body if requested.
    if (!empty($this->component_data['has_wrapping_newlines'])) {
      // Argh. WTF. Newline drama. Hook definitions have newlines at start and
      // end. But when we define code ourselves, it's a pain to have to put
      // those in.
      $this->component_data['body'] = array_slice($this->component_data['body'], 1, -1);
    }

    $function_code = array();
    $function_code = array_merge($function_code, $this->docBlock($this->component_data['doxygen_first']));

    $declaration = str_replace('£', '$', $this->component_data['declaration']);

    $function_code[] = $declaration . ' {';

    if (isset($this->component_data['body'])) {
      $body = is_array($this->component_data['body'])
        ? $this->component_data['body']
        : array($this->component_data['body']);

      // Little bit of sugar: to save endless escaping of $ in front of
      // variables in code body, you can use £.
      $body = array_map(function($line) {
          return str_replace('£', '$', $line);
        }, $body);

      // Add indent.
      if (!empty($this->component_data['body_indent'])) {
        $padding = str_repeat(' ', $this->component_data['body_indent']);
        $body = array_map(function($string) use ($padding) {
          return empty($string) ? $string : "$padding$string";
        }, $body);
      }

      $function_code = array_merge($function_code, $body);
    }

    $function_code[] = "}";

    return [
      'function' => [
        'role' => 'function',
        'content' => $function_code,
      ],
    ];
  }

}
