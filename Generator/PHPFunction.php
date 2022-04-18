<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;

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
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      // TODO: make this required when https://github.com/joachim-n/mutable-typed-data/issues/7
      // is fixed.
      'function_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      'docblock_inherit' => PropertyDefinition::create('boolean')
        ->setInternal(TRUE)
        ->setLiteralDefault(FALSE),
      // Deprecated: use function_docblock_lines instead.
      'doxygen_first' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      // Lines for the class docblock.
      // If there is more than one line, a blank link is inserted automatically
      // after the first one.
      // Or multiple string?
      'function_docblock_lines' => PropertyDefinition::create('mapping')
        ->setDefault(DefaultDefinition::create()
          ->setCallable([static::class, 'defaultDocblockLines'])
      ),
      'doxygen_tag_lines' => PropertyDefinition::create('string')
        ->setLabel("Doxygen tags to go after the standard ones.")
        ->setMultiple(TRUE)
        ->setInternal(TRUE),
      'declaration' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      'parameters' => PropertyDefinition::create('complex')
        ->setMultiple(TRUE)
        ->setInternal(TRUE)
        ->setProperties([
          'name' => PropertyDefinition::create('string'),
          'type' => PropertyDefinition::create('string')
            // Need to give a type, otherwise PHPCS will complain in tests!
            ->setLiteralDefault('string'),
          'description' => PropertyDefinition::create('string')
            ->setLiteralDefault('Parameter description.'),
          'default_value' => PropertyDefinition::create('string'),
        ]),
      'body' => PropertyDefinition::create('string')
        ->setMultiple(TRUE)
        ->setInternal(TRUE),
      // Whether code lines in the 'body' property are already indented relative
      // to the indentation of function as a whole.
      'body_indented' => PropertyDefinition::create('boolean')
        ->setInternal(TRUE)
        ->setLiteralDefault(FALSE),
    ]);

    return $definition;
  }

  public static function defaultDocblockLines($data_item) {
    if ($data_item->getParent()->docblock_inherit->value) {
      return [
        '{@inheritdoc}',
      ];
    }
    else {
      return [
        'TODO: write function documentation.',
      ];
    }
  }

  /**
   * Gets the bare lines to format as the docblock.
   *
   * @return string[]
   *   An array of lines.
   */
  protected function getFunctionDocBlockLines() {
    $lines = [];

    // Check the deprecated 'doxygen_first' property first, as code that doesn't
    // use this will have it empty.
    if (!$this->component_data->doxygen_first->isEmpty()) {
      $lines[] = $this->component_data->doxygen_first->value;
    }
    else {
      $lines = $this->component_data->function_docblock_lines->value;

      if (count($lines) > 1) {
        // If there is more than one line, splice in a blank line after the
        // first one.
        array_splice($lines, 1, 0, '');
      }
    }

    if (!$this->component_data->parameters->isEmpty()) {
      $lines[] = '';

      foreach ($this->component_data->parameters as $parameter_data) {
        $param_name_line = '@param ';
        // ARGH TODO! Shouldn't this happen somewhere else???
        $parameter_data->type->applyDefault();
         if (!empty($parameter_data->type->value)) {
          $param_name_line .= $parameter_data->type->value . ' ';
        }
        $param_name_line .= '$' . $parameter_data->name->value;
        $lines[] = $param_name_line;
        $lines[] = '  ' . $parameter_data->description->value;
      }
    }

    if (!$this->component_data->doxygen_tag_lines->isEmpty()) {
      $lines = array_merge($lines, [''], $this->component_data->doxygen_tag_lines->values());
    }

    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $function_code = [];
    $function_code = array_merge($function_code, $this->docBlock($this->getFunctionDocBlockLines()));

    $declaration = str_replace('£', '$', $this->component_data['declaration']);

    if (!$this->component_data->parameters->isEmpty()) {
      // Remove the final closing ')'.
      $declaration = rtrim($declaration, ')');

      $parameters = [];
      foreach ($this->component_data->parameters as $parameter_data) {
        $parameter = '';
        if (!$parameter_data->type->isEmpty()) {
          $parameter .= $parameter_data->type->value . ' ';
        }
        $parameter .= '$' . $parameter_data->name->value;

        if (!$parameter_data->default_value->isEmpty()) {
          $parameter .= ' = ' . $parameter_data->default_value->value;
        }

        $parameters[] = $parameter;
      }

      $declaration .= implode(', ', $parameters) . ')';
    }

    $function_code[] = $declaration . ' {';

    if (isset($this->component_data['body'])) {
      $body = is_array($this->component_data['body'])
        ? $this->component_data['body']
        : [$this->component_data['body']];

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

    // TODO: remove this when https://github.com/joachim-n/mutable-typed-data/issues/7
    // is fixed.
    assert(!empty($this->component_data['function_name']));

    return [
      'function' => [
        'role' => 'function',
        'function_name' => $this->component_data['function_name'],
        'content' => $function_code,
      ],
    ];
  }

}
