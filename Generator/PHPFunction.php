<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;

/**
 * Generator base class for functions.
 *
 * (We can't call this 'Function', as that's a reserved word.)
 *
 * Properties include:
 *    - 'declaration': The function declaration, including the function name
 *      and parameters, up to the closing parenthesis. Should not however
 *      include the opening brace of the function body.
 *    - 'body' The code of the function. The character '£' is replaced with
 *      '$' as a convenience to avoid having to keep escaping names of
 *      variables. This can be in one of the following forms:
 *      - a string, not including the enclosing function braces or the opening
 *        or closing newlines.
 *        TODO: This is not currently working, but doesn't matter as
 *        Hooks::getTemplates() always returns an array of lines in 'template'
 *        even if that's just the analysis body code.
 *      - an array of lines of code. These should not have their newlines.
 */
class PHPFunction extends BaseGenerator {

  use PHPFormattingTrait;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'function_name' => [
        'internal' => TRUE,
      ],
      'docblock_inherit' => [
        'internal' => TRUE,
        'default' => FALSE,
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          if ($value) {
            $component_data['function_docblock_lines'] = ['{@inheritdoc}'];
          }
        },
      ],
      // Deprecated: use function_docblock_lines instead.
      'doxygen_first' => [
        'internal' => TRUE,
        'default' => 'TODO: write function documentation.',
      ],
      // Lines for the class docblock.
      // If there is more than one line, a blank link is inserted automatically
      // after the first one.
      'function_docblock_lines' => [
        'format' => 'array',
        'internal' => TRUE,
        // No default, as most generators don't use this yet.
      ],
      'declaration' => [
        'internal' => TRUE,
      ],
      'body' => [
        'internal' => TRUE,
      ],
      // Whether code lines in the 'body' property are already indented relative
      // to the indentation of function as a whole.
      'body_indented' => [
        'internal' => TRUE,
        'format' => 'boolean',
        'default' => FALSE,
      ],
    ];
  }

  /**
   * Gets the bare lines to format as the docblock.
   *
   * @return string[]
   *   An array of lines.
   */
  protected function getFunctionDocBlockLines() {
    $lines = [];

    if (!empty($this->component_data['function_docblock_lines'])) {
      $lines = $this->component_data['function_docblock_lines'];

      if (count($lines) > 1) {
        // If there is more than one line, splice in a blank line after the
        // first one.
        array_splice($lines, 1, 0, '');
      }
    }
    elseif (!empty($this->component_data['doxygen_first'])) {
      $lines[] = $this->component_data['doxygen_first'];
    }

    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $function_code = array();
    $function_code = array_merge($function_code, $this->docBlock($this->getFunctionDocBlockLines()));

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
      if (empty($this->component_data['body_indented'])) {
        $body = $this->indentCodeLines($body);
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
