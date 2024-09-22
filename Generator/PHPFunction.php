<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use CaseConverter\CaseString;
use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\DocBlock;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;
use DrupalCodeBuilder\Generator\Render\PhpValue;
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
 *    - Contained PHPFunctionBodyLines components.
 *    - Both the property value and contained PHPFunctionBodyLines components,
 *      in which case contained component lines either go last, or are inserted
 *      to replace a token: see self::getContents().
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
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

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
      'doxygen_tag_lines' => PropertyDefinition::create('mapping')
        ->setLabel("Doxygen tags to go after the standard ones")
        ->setInternal(TRUE),
      // An attribute class for the method.
      // WARNING: Only for methods, only supports one attribute, only supports
      // a class with no parameters.
      'attribute' => PropertyDefinition::create('string')
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
          'by_reference' => PropertyDefinition::create('boolean'),
          // The typehint of the parameter. If this is a class or interface, use
          // the fully-qualified form: this will produce import statements for
          // the file automatically.
          'typehint' => PropertyDefinition::create('string')
            // Need to give a type, otherwise PHPCS will complain in tests!
            ->setLiteralDefault('string'),
          // The description of the parameter. This may be omitted if
          // 'docblock_inherit' is TRUE.
          'nullable' => PropertyDefinition::create('boolean'),
          'description' => PropertyDefinition::create('string')
            ->setLiteralDefault('Parameter description.'),
          'default_value' => PropertyDefinition::create('string'),
        ]),
      // Whether to put parameter type declarations for PHP primitive types.
      // Defaults to FALSE as lots of Drupal functions do this for BC.
      'use_primitive_parameter_type_declarations' => PropertyDefinition::create('boolean')
          ->setLiteralDefault(FALSE),
      // NOTE: only works when 'declaration' is not used.
      'return_type' => PropertyDefinition::create('string'),
      'body' => PropertyDefinition::create('string')
        ->setMultiple(TRUE)
        ->setInternal(TRUE),
      // Whether the contents of contained components of content type 'line'
      // overrides the 'body' property, or are merged. If merging, the magic
      // string 'CONTAINED_COMPONENTS' can be used to insert the components
      // instead of appending them.
      'body_overriden_by_contained' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault(FALSE),
      // Whether code lines in the 'body' property are already indented relative
      // to the indentation of function as a whole.
      'body_indented' => PropertyDefinition::create('boolean')
        ->setInternal(TRUE)
        ->setLiteralDefault(FALSE),
      // Whether this is a procedural function or a class method. Fiddly but
      // less so than making a subclass just for this, probably. Defaults to
      // TRUE as most things are methods.
      'method' => PropertyDefinition::create('boolean')
        ->setInternal(TRUE)
        ->setLiteralDefault(TRUE),
    ]);
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
    $function_code = array_merge($function_code, $this->getFunctionDocBlockLines());

    if ($function_attributes = $this->getFunctionAttributes()) {
      $function_code = array_merge($function_code, $function_attributes->render());
    }

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
      // There may be both property data and contained components. Contained
      // components override the body if it is set and if
      // 'body_overriden_by_contained' is TRUE.
      $has_body_from_component_data = !$this->component_data->body->isEmpty();
      $has_body_from_contained_components = $this->hasContainedComponentsOfContentType('line');

      $let_body_from_contained_components_override_body_from_component_data =
        $this->component_data->body_overriden_by_contained->value
        &&
        $has_body_from_contained_components;

      if ($has_body_from_component_data && !$let_body_from_contained_components_override_body_from_component_data) {
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
   * Gets the docblock lines, with docblock formatting.
   *
   * @return string[]
   *   An array of lines.
   */
  protected function getFunctionDocBlockLines() {
    if ($this->component_data->method->value) {
      $docblock = DocBlock::method();
    }
    else {
      $docblock = DocBlock::function();
    }

    // TODO: no need for default value in properties
    // An inherit docblock is handled as a default value. Nothing else to do.
    if ($this->component_data->docblock_inherit->value) {
      return $docblock->inheritdoc()->render();
    }

    $lines = $this->component_data->function_docblock_lines->value;

    foreach ($lines as $line) {
      $docblock[] = $line;
    }

    if (!$this->component_data->parameters->isEmpty() || isset($this->containedComponents['parameter'])) {
      // Handle parameters set as a property first, then contained components.
      foreach ($this->component_data->parameters as $parameter_data) {
        // ARGH TODO! Shouldn't this happen somewhere else???
        $parameter_data->typehint->applyDefault();

        $docblock->param($parameter_data->typehint->value, $parameter_data->name->value, $parameter_data->description->value, $parameter_data->by_reference->value);
      }

      foreach ($this->containedComponents['parameter'] as $parameter_component) {
        $parameter_data = $parameter_component->getContents();

        $docblock->param($parameter_data['typehint'], $parameter_data['parameter_name'], $parameter_data['description'], $parameter_data['by_reference'] ?? FALSE);
      }
    }

    if (!$this->component_data->doxygen_tag_lines->isEmpty()) {
      foreach ($this->component_data->doxygen_tag_lines->value as $tag_data) {
        $tag = array_shift($tag_data);
        $docblock->$tag(...$tag_data);
      }
    }

    return $docblock->render();
  }

  /**
   * Produces the function attributes.
   *
   * @return \DrupalCodeBuilder\Generator\Render\PhpAttributes|null
   *   An attribute object if this function has attributes.
   */
  protected function getFunctionAttributes(): ?PhpAttributes {
    if ($this->component_data->attribute->isEmpty()) {
      return NULL;
    }
    else {
      return PhpAttributes::method($this->component_data->attribute->value);
    }
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
   *    - 'by_reference'
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
      $declaration_line_params[] = $this->buildParameter($parameter_info);
    }

    if ($options['break_declaration']) {
      // The function declaration up to the opening '(' is one line.
      $code[] = $declaration_line;

      $last_index = count($declaration_line_params) - 1;
      foreach ($declaration_line_params as $index => $param) {
        $code[] = '  ' . $param . ',';
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
   * Builds the string for a single function parameter.
   *
   * @param array $parameter_info
   *   The array of data for the parameter from the 'parameters' property.
   *
   * @return string
   *   The string for the parameter in the function declaration, with any
   *   prefixes and the type.
   */
  protected function buildParameter(array $parameter_info): string {
    // Allow for parameter info from both code analysis and from a
    // PHPFunctionParameter component, which don't use the same key (because
    // using 'name' as a data property name causes issues as it's also a class
    // property name on the DataItem class.
    $parameter_name = $parameter_info['parameter_name'] ?? $parameter_info['name'];

    $parameter_pieces = [];

    $parameter_has_type = !empty($parameter_info['typehint']);
    if ($parameter_has_type) {
      // Don't type hint primitive types unless explicitly set to do so.
      $parameter_should_use_type =
        !in_array($parameter_info['typehint'], ['string', 'bool', 'mixed', 'int'])
        ||
        $this->component_data->use_primitive_parameter_type_declarations->value;
    }

    if (!empty($parameter_should_use_type)) {
      $parameter_pieces[] =
        (!empty($parameter_info['nullable']) ? '?' : '')
        . $parameter_info['typehint'];
    }

    $parameter_symbol =
      (!empty($parameter_info['by_reference']) ? '&' : '')
      . '$'
      . $parameter_name;

    $parameter_pieces[] = $parameter_symbol;

    if (isset($parameter_info['default_value'])) {
      $parameter_pieces[] = '= ' . PhpValue::create($parameter_info['default_value'])->renderInline();
    }

    return implode(' ', $parameter_pieces);
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
