<?php

namespace DrupalCodeBuilder\Generator\Render;

/**
 * Renderer for PHP attributes.
 */
class PhpAttributes extends PhpRenderer {

  protected bool $forceInline = FALSE;

  /**
   * Constructor.
   *
   * Note that this class must not be instantiated with 'new': see the various
   * static factory methods.
   *
   * @param string $attributeClassName
   *   The name of the attribute class, either with or without the initial '\'.
   *   (We have to support both forms because plugin attribute classes are
   *   stored without, and plugin property types are stored with, WTF.)
   * @param mixed $data
   *   The data for the attribute. For an attribute with no parameters, use
   *   NULL. A scalar value will be rendered inline with the attribute class.
   *   An array value will be rendered over multiple indented lines.
   * @param $comments
   *   An array of comments for the attribute's top-level properties. Keys are
   *   property names, values are the comment text.
   * @param int $indentLevel
   *   The overall indent level for the code lines.
   */
  public function __construct(
    protected string $attributeClassName,
    protected mixed $data,
    protected array $comments,
    protected int $indentLevel,
  ) {
  }

  /**
   * Creates a new attribute for a class.
   */
  public static function class($attribute_class_name, $data = NULL, $comments = []) {
    return new static($attribute_class_name, $data, $comments, 0);
  }

  /**
   * Creates a new attribute for a method.
   */
  public static function method($attribute_class_name, $data = NULL, $comments = []) {
    return new static($attribute_class_name, $data, $comments, 2);
  }

  /**
   * Creates a new attribute for a nested object.
   */
  public static function object($attribute_class_name, $data = NULL, $comments = []) {
    // TODO: indent level is meaningless here.
    return new static($attribute_class_name, $data, $comments, 0);
  }

  /**
   * Sets the rendering to be inline even for an array of parameters.
   *
   * @return self
   */
  public function forceInline(): self {
    $this->forceInline = TRUE;
    return $this;
  }

  /**
   * Renders the attribute to an array of code lines.
   */
  public function render() {
    $lines = [];

    $class_name_prefix = str_starts_with($this->attributeClassName, '\\') ? '' : '\\';
    $class_name = $class_name_prefix . $this->attributeClassName;

    if (empty($this->data)) {
      // Just the attribute class.
      $lines[] = '#[' . $class_name . ']';
    }
    elseif (is_scalar($this->data)) {
      // Attribute with a single scalar value.
      $lines[] = '#[' .
        $class_name .
        '(' . PhpValue::create($this->data)->renderInline() . ')]';
    }
    elseif ($this->forceInline) {
      // Attribute with an inline array.
      $lines[] = '#[' .
        $class_name .
        '(' .
        $this->renderAttributeParametersInline($this->data) .
        ')]';
    }
    else {
      // Attribute with multi-line data.
      $lines[] = '#[' . $class_name . '(';

      $this->renderAttributeParameters($lines, $this->data);

      $lines[] = ')]';
    }

    return $lines;
  }

  /**
   * Renders the attribute nested within another attribute.
   *
   * @param array &$lines
   *   The lines of code, passed by reference.
   * @param string $declaration_line
   *   The partial declaration line.
   * @param int $nesting
   *   The nesting level.
   */
  public function renderNestedObject(&$lines, $declaration_line, $nesting): void {
    $class_name_prefix = str_starts_with($this->attributeClassName, '\\') ? '' : '\\';
    $declaration_line .= 'new ' . $class_name_prefix . $this->attributeClassName . '(';

    if (is_string($this->data)) {
      $declaration_line .= $this->quoteString($this->data);
      $declaration_line .= '),';
    }
    // TODO: support nested arrays. Not needed yet.

    $lines[] = $declaration_line;
  }

  /**
   * Recursively renders this attribute's parameters.
   *
   * @param array &$lines
   *   The lines of code, passed by reference.
   * @param array $data
   *   The data to render.
   * @param int $nesting
   *   The nesting level.
   */
  public function renderAttributeParameters(&$lines, $data, $nesting = 0): void {
    $indent = str_repeat('  ', $this->indentLevel + $nesting + 1);

    foreach ($data as $key => $value) {
      if (is_numeric($key)) {
        // Numeric keys are not shown.
        $declaration_line = "{$indent}";
      }
      else {
        // Render a comment if there is a description for this key.
        // Comments only go on the top-level keys of the attribute, not inner
        // data arrays.
        // dump($thicomments);
        if (isset($this->comments[$key])) {
          $wrapped_comment_lines = $this->wrapLine($this->comments[$key], $nesting);
          foreach ($wrapped_comment_lines as $comment_line) {
            $lines[] = $indent. '// ' . $comment_line;
          }
        }

        $declaration_line = "{$indent}{$key}: ";
      }

      if (is_scalar($value)) {
        $declaration_line .= PhpValue::create($value)->renderInline() . ',';
        $lines[] = $declaration_line;
      }
      elseif (is_object($value)) {
        $value->renderNestedObject($lines, $declaration_line, $nesting);
      }
      elseif (is_array($value)) {
        // Array of values. Recurse into this method.
        $declaration_line .= '[';
        $lines[] = $declaration_line;

        $lines = array_merge($lines, PhpValue::create($value, embedded_level: $nesting + 1)->renderMultiline());

        $lines[] = $indent . "],";
      }
    }
  }

  /**
   * Renders attribute parameters as a single line.
   *
   * @param array $data
   *
   * @return string
   *   The rendered parameters.
   */
  protected function renderAttributeParametersInline(array $data): string {
    $pieces = [];
    foreach ($data as $key => $value) {
      $piece = '';
      if (!is_numeric($key)) {
        $piece .= "{$key}: ";
      }

      $piece .= PhpValue::create($value)->renderInline();

      $pieces[] = $piece;
    }

    return implode(', ', $pieces);
  }

  /**
   * Wraps a line to the specified width.
   *
   * @param string $line
   *   The line of text to wrap.
   * @param int $indent_level
   *   The indent level that this line should be indented by.
   *
   * @return array
   *   An array of lines.
   */
  protected function wrapLine(string $line, int $indent_level): array {
    // Wrap the description to 80 characters minus the indentation and the
    // comment marker.
    $wrapped_line = wordwrap($line, 80 - (($indent_level + 1 ) * 2) - 3);
    $wrapped_lines = explode("\n", $wrapped_line);
    return $wrapped_lines;
  }

}
