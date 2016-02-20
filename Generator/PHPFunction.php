<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\PHPFunction.
 */

namespace ModuleBuilder\Generator;

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
   *    - 'body_indent': (options) The number of spaces to add to the start of
   *      each line, if 'body' is an array.
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    // Set defaults.
    $component_data += array(
      'code_file' => '%module.module',
      'doxygen_first' => 'TODO: write function documentation',
    );

    $this->code_file = $component_data['code_file'];

    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
  }

  /**
   * Return this component's parent in the component tree.
   */
  function containingComponent() {
    return $this->code_file;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    // TEMPORARY. Subclasses of this don't yet use the constructor, but
    // return an array of data in componentFunctions(). Hack this into our
    // component data.
    $data = $this->componentFunctions();

    if (is_array($data) && count($data)) {
      // Some implementations return more than one and need to be changed.
      $function_data = array_pop($data);
      // Override the defaults which are already set.
      $this->component_data = (array) $function_data + $this->component_data;
    }

    if (isset($function_data['code'])) {
      $this->component_data['body'] = $function_data['code'];
    }
    if (!empty($function_data['has_wrapping_newlines'])) {
      // Argh. WTF. Newline drama. Hook definitions have newlines at start and
      // end. But when we define code ourselves, it's a pain to have to put
      // those in.
      $this->component_data['body'] = trim($this->component_data['body'], "\n");
    }
    // END TEMPORARY.

    $function_code = array();
    $function_code[] = $this->docBlock($this->component_data['doxygen_first']);

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
          return "$padding$string";
        }, $body);
      }

      $function_code = array_merge($function_code, $body);
    }

    $function_code[] = "}";

    return $function_code;
  }

  /**
   * Called by ModuleCodeFile to collect functions from its child components.
   *
   * @return
   *  An array keyed by function name (placeholders allowed), whose properties
   *  are:
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
   */
  public function componentFunctions() {
    return array();
  }

}
