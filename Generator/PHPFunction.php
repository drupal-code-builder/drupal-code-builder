<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator base class for functions.
 *
 * (We can't call this 'Function', as that's a reserved word.)
 *
 * This generator has properties that can be used in several different
 * combinations:
 *
 * - For the declaration, either set complete declaration string, or the
 *   separate name, prefixes, parameters, and return type. The reason for this
 *   is that analysis data will have the declaration string, but for functions
 *   completely assembled in code it's easier to define each element of the
 *   declaration.
 * - For the parameters, both the property value and contained
 *   PHPFunctionParameter components are combined in that order.
 * - The code of the function can either come from:
 *    - The getFunctionBody() method if a subclass overrides it.
 *    - The property value.
 *    - Contained PHPFunctionLine components.
 *    - Both the property value and contained PHPFunctionLine components, in
 *      which case contained component lines either go last, or are inserted to
 *      replace a token: see self::getContents().
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
      // The method name (without the ()).
      // TODO: make this required when https://github.com/joachim-n/mutable-typed-data/issues/7
      // is fixed.
      'function_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      'docblock_inherit' => PropertyDefinition::create('boolean')
        ->setInternal(TRUE)
        ->setLiteralDefault(FALSE),
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
      // An array of prefixes such as 'static', 'public'.
      // Not yet compatible with 'declaration' property.
      'prefixes' => PropertyDefinition::create('string')
        ->setMultiple(TRUE),
      // Not yet compatible with 'declaration' property.
      // Has no effect if 'parameters' is empty.
      'break_declaration' => PropertyDefinition::create('boolean')
        ->setDescription('If TRUE, the declaration parameters are each on a single line.'),
      'parameters' => PropertyDefinition::create('complex')
        ->setMultiple(TRUE)
        ->setInternal(TRUE)
        ->setProperties([
          // The name of the parameter, without the initial $.
          'name' => PropertyDefinition::create('string'),
          // The typehint of the parameter. If this is a class or interface, use
          // the fully-qualified form: this will produce import statements for
          // the file automatically.
          'typehint' => PropertyDefinition::create('string')
            // Need to give a type, otherwise PHPCS will complain in tests!
            ->setLiteralDefault('string'),
          // The description of the parameter. This may be omitted if
          // 'docblock_inherit' is TRUE.
          'description' => PropertyDefinition::create('string')
            ->setLiteralDefault('Parameter description.'),
          'default_value' => PropertyDefinition::create('string'),
        ]),
      // NOTE: only works when 'declaration' is not used.
      'return_type' => PropertyDefinition::create('string'),
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
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'function';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    $function_code = [];
    $function_code = array_merge($function_code, $this->docBlock($this->getFunctionDocBlockLines()));

    // If the declaration isn't set, built it from property values and contained
    // parameter components.
    if (empty($this->component_data->declaration->value)) {
      $parameters = [];

      // Handle parameters set as a property first, then contained components.
      foreach ($this->component_data->parameters as $parameter_data) {
        $parameters[] = $parameter_data->export();
      }

      foreach ($this->containedComponents['parameter'] as $parameter_component) {
        $parameters[] = $parameter_component->getContents();
      }

      $declaration_lines = $this->buildMethodDeclaration(
        $this->component_data->function_name->value,
        $parameters,
        [
          'prefixes' => $this->component_data->prefixes->values(),
          'break_declaration' => $this->component_data->break_declaration->value,
        ],
        $this->component_data->return_type->value,
      );

      $function_code = array_merge($function_code, $declaration_lines);
    }
    else {
      $declaration = str_replace('£', '$', $this->component_data['declaration']);

      if (!$this->component_data->parameters->isEmpty()) {
        // Remove the final closing ')'.
        $declaration = rtrim($declaration, ')');

        $parameters = [];
        foreach ($this->component_data->parameters as $parameter_data) {
          $parameter = '';
          if (!$parameter_data->typehint->isEmpty()) {
            $parameter .= $parameter_data->typehint->value . ' ';
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
    }

    $body = [];
    if ($body = $this->getFunctionBody()) {
      // Do nothing; assignment suffices.
    }
    else {
      // Use both property data and contained components.
      if (isset($this->component_data['body'])) {
        $body = is_array($this->component_data['body'])
          ? $this->component_data['body']
          : [$this->component_data['body']];
      }

      if (isset($this->containedComponents['line'])) {
        $contained_component_code_lines = [];
        foreach ($this->containedComponents['line'] as $parameter_component) {
          $contained_component_code_lines = array_merge($contained_component_code_lines, $parameter_component->getContents());
        }

        // Contained component content lines are either added at the end, or
        // to replace the magic 'CONTAINED_COMPONENTS' lines.
        if (in_array('CONTAINED_COMPONENTS', $body)) {
          $index = array_search('CONTAINED_COMPONENTS', $body);

          array_splice($body, $index, 1, $contained_component_code_lines);
        }
        else {
          $body = array_merge($body, $contained_component_code_lines);
        }
      }
    }

    // Little bit of sugar: to save endless escaping of $ in front of
    // variables in code body, you can use £.
    $body = array_map(function($line) {
        return str_replace('£', '$', $line);
      }, $body);

    // Add indent.
    if (empty($this->component_data->body_indented->value)) {
      $body = $this->indentCodeLines($body);
    }

    $function_code = array_merge($function_code, $body);

    $function_code[] = "}";

    // TODO: remove this when https://github.com/joachim-n/mutable-typed-data/issues/7
    // is fixed.
    assert(!empty($this->component_data['function_name']));

    return $function_code;
  }

  /**
   * Gets the bare lines to format as the docblock.
   *
   * @return string[]
   *   An array of lines.
   */
  protected function getFunctionDocBlockLines() {
    $lines = $this->component_data->function_docblock_lines->value;

    // An inherit docblock is handled as a default value. Nothing else to do.
    if ($this->component_data->docblock_inherit->value) {
      return $lines;
    }

    if (count($lines) > 1) {
      // If there is more than one line, splice in a blank line after the
      // first one.
      array_splice($lines, 1, 0, '');
    }

    if (!$this->component_data->parameters->isEmpty() || isset($this->containedComponents['parameter'])) {
      $lines[] = '';

      // Handle parameters set as a property first, then contained components.
      foreach ($this->component_data->parameters as $parameter_data) {
        $param_name_line = '@param ';
        // ARGH TODO! Shouldn't this happen somewhere else???
        $parameter_data->typehint->applyDefault();
         if (!empty($parameter_data->typehint->value)) {
          $param_name_line .= $parameter_data->typehint->value . ' ';
        }
        $param_name_line .= '$' . $parameter_data->name->value;
        $lines[] = $param_name_line;

        // TODO: why default not applied?
        // Generate a parameter description from the name if none was given.
        if (empty($parameter_data->description->value)) {
          // TODO: add a 'lower' case to case converter.
          $parameter_data->description = CaseString::snake('The_' . $parameter_data->name->value)->sentence() . '.';
        }

        // Wrap the description to 80 characters minus the indentation.
        $indent_count =
          2 // Class code indent.
          + 2 // Space and the doc comment asterisk.
          + 3; // Indentation for the parameter description.
        $wrapped_description = wordwrap($parameter_data->description->value, 80 - $indent_count);
        $wrapped_description_lines = explode("\n", $wrapped_description);

        foreach ($wrapped_description_lines as $line) {
          $lines[] = '  ' . $line;
        }
      }

      foreach ($this->containedComponents['parameter'] as $parameter_component) {
        $parameter_data = $parameter_component->getContents();

        $lines[] = "@param {$parameter_data['typehint']} \${$parameter_data['parameter_name']}";
        $lines[] = '  ' . $parameter_data['description'];
      }
    }

    if (!$this->component_data->doxygen_tag_lines->isEmpty()) {
      $lines = array_merge($lines, [''], $this->component_data->doxygen_tag_lines->values());
    }

    return $lines;
  }

  /**
   * Builds the declaration code lines.
   *
   * @param string $name
   *   The function name.
   * @param array $parameters
   *   An array of parameters. The key is ignored. Each item is an array with
   *   keys:
   *    - 'name'
   *    - 'typehint'
   * @param array $options
   *   An array of options:
   *    - 'prefixes': An array of the function's prefixes.
   *    - 'break_declaration': Boolean to set whether to put each parameter on
   *      its own line.
   * @param string $return_type
   *   The return type.
   *
   * @return array
   *   An array of code lines.
   */
  protected function buildMethodDeclaration($name, $parameters = [], $options = [], string $return_type = NULL): array {
    $options += [
      'prefixes' => [],
      'break_declaration' => FALSE,
    ];

    // Override break_declaration if there are no parameters.
    if (empty($parameters)) {
      $options['break_declaration'] = FALSE;
    }

    $code = [];

    if ($return_type) {
      $closing = "): $return_type {";
    }
    else {
      $closing = ') {';
    }

    $declaration_line = '';
    foreach ($options['prefixes'] as $prefix) {
      $declaration_line .= $prefix . ' ';
    }
    $declaration_line .= 'function ' . $name . '(';
    $declaration_line_params = [];
    foreach ($parameters as $parameter_info) {
      // Allow for parameter info from both code analysis and from a
      // PHPFunctionParameter component, which don't use the same key (because
      // using 'name' as a data property name causes issues as it's also a class
      // property name on the DataItem class.
      $parameter_name = $parameter_info['parameter_name'] ?? $parameter_info['name'];

      if (!empty($parameter_info['typehint']) && in_array($parameter_info['typehint'], ['string', 'bool', 'mixed', 'int'])) {
        // Don't type hint scalar types.
        $declaration_line_params[] = '$' . $parameter_name;
      }
      elseif (!empty($parameter_info['typehint'])) {
        $declaration_line_params[] = $parameter_info['typehint'] . ' $' . $parameter_name;
      }
      else {
        $declaration_line_params[] = '$' . $parameter_name;
      }
    }

    if ($options['break_declaration']) {
      // The function declaration up to the opening '(' is one line.
      $code[] = $declaration_line;

      $last_index = count($declaration_line_params) - 1;
      foreach ($declaration_line_params as $index => $param) {
        $code[] = '  ' . $param . ( $index == $last_index ? '' : ',' );
      }

      $code[] = $closing;
    }
    else {
      $declaration_line .= implode(', ', $declaration_line_params);
      $declaration_line .= $closing;

      $code[] = $declaration_line;
    }

    return $code;
  }

  /**
   * Gets body lines of the function.
   *
   * Helper to allow classes to override the code lines from the property
   * value and contents.
   *
   * @return string[]
   *   An array of lines.
   */
  protected function getFunctionBody(): array {
    return [];
  }

}
