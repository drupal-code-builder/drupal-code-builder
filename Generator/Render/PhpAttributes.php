<?php

namespace DrupalCodeBuilder\Generator\Render;

/**
 * Renderer for PHP attributes.
 */
class PhpAttributes {

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
   *   The data for the attribute.
   * @param int $indentLevel
   *   The overall indent level for the code lines.
   */
  public function __construct(
    protected string $attributeClassName,
    protected mixed $data,
    protected int $indentLevel,
  ) {
  }

  /**
   * Creates a new attribute for a class.
   */
  public static function class($attribute_class_name, $data) {
    return new static($attribute_class_name, $data, 0);
  }

  /**
   * Creates a new attribute for a nested object.
   */
  public static function object($attribute_class_name, $data) {
    // TODO: indent level is meaningless here.
    return new static($attribute_class_name, $data, 0);
  }

  /**
   * Renders the attribute to an array of code lines.
   */
  public function render() {
    $lines = [];

    $class_name_prefix = str_starts_with($this->attributeClassName, '\\') ? '' : '\\';
    $lines[] = '#[' . $class_name_prefix . $this->attributeClassName . '(';

    $this->renderArray($lines, $this->data);

    $lines[] = ')]';

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
      $declaration_line .= '"' . $this->data . '"';
      $declaration_line .= '),';
    }
    // TODO: support nested arrays. Not needed yet.

    $lines[] = $declaration_line;
  }

  /**
   * Recursively renders this attribute's data.
   *
   * @param array &$lines
   *   The lines of code, passed by reference.
   * @param array $data
   *   The data to render.
   * @param int $nesting
   *   The nesting level.
   * @param bool $attribute_nesting
   *   Whether this is nested data in an attribute or not.
   */
  public function renderArray(&$lines, $data, $nesting = 0, $attribute_nesting = FALSE): void {
    $indent = str_repeat('  ', $this->indentLevel + $nesting + 1);

    foreach ($data as $key => $value) {
      if (is_numeric($key)) {
        // Numeric keys are not shown.
        $declaration_line = "{$indent}";
      }
      else {
        // Keys need to be quoted for all levels except the first level of an
        // attribute.
        if ($attribute_nesting) {
          $key = "'$key'";
        }

        $declaration_line = "{$indent}{$key}";

        // Argh too much switching on this - change to two separate methods??
        if ($attribute_nesting) {
          $declaration_line .= ' => ';
        }
        else {
          $declaration_line .= ': ';
        }
      }

      if (is_string($value)) {
        $value = '"' . $value . '"';

        $declaration_line .= "{$value},";
        $lines[] = $declaration_line;
      }
      elseif (is_bool($value)) {
        $value = $value ? 'TRUE' : 'FALSE';

        $declaration_line .= "{$value},";
        $lines[] = $declaration_line;
      }
      elseif (is_object($value)) {
        $value->renderNestedObject($lines, $declaration_line, $nesting);
      }
      elseif (is_array($value)) {
        // Array of values. Recurse into this method.
        $declaration_line .= '[';
        $lines[] = $declaration_line;

        $this->renderArray($lines, $value, $nesting + 1, attribute_nesting: TRUE);

        $lines[] = $indent . "],";
      }
    }
  }

}