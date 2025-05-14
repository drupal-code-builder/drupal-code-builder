<?php

namespace DrupalCodeBuilder\Generator\Render;

use CaseConverter\CaseString;
use PhpParser\Comment;

/**
 * Renderer for a docblock.
 *
 * This renders lines for the entire docblock, with line wrapping and the
 * docblock comment formatting. It does not, however, indent a docblock if it
 * for a class method, as the class generator takes care of indenting all of its
 * code.
 *
 * Instantiate a new DocBlock object with one of the static methods, for
 * example:
 * @code
 * $docblock = DocBlock::method();
 * @endcode
 *
 * Use array access to add paragraphs:
 * @code
 * $docblock[] = 'Some documentation text.';
 * @endcode
 *
 * Add tags by calling the name of the tag. Parameters depend on the type of
 * tag.
 * @code
 * $docblock->param('param_name', 'type', 'description');
 * $docblock->return('type', 'description');
 * $docblock->my_tag('text on same line as @my_tag', 'description');
 * $docblock->Annotation(); // Case is respected.
 * @endcode
 */
class DocBlock implements \ArrayAccess {

  /**
   * Array of paragraph text.
   *
   * @var array
   */
  protected $paragraphs = [];

  /**
   * Array of tags.
   *
   * @var array
   */
  protected $tags = [];

  public function __construct(
    protected int $indentLevel,
    protected string $initialTag = '',
  ) {
  }

  /**
   * Creates a new DocBlock for a class.
   */
  public static function class() {
    return new static(0);
  }

  /**
   * Creates a new DocBlock for a file.
   */
  public static function file() {
    return new static(0, 'file');
  }

  /**
   * Creates a new DocBlock for a procedural function.
   */
  public static function function() {
    return new static(0);
  }

  /**
   * Creates a new DocBlock for a class method.
   */
  public static function method() {
    return new static(1);
  }

  /**
   * Creates a new DocBlock for a class property.
   */
  public static function property() {
    return new static(1);
  }

  /**
   * Creates a new DocBlock for a class constant.
   */
  public static function constant() {
    return new static(1);
  }

  /**
   * Adds an '@inheritdoc' line to the docblock.
   */
  public function inheritdoc(): static {
    $this->paragraphs[] = '{@inheritdoc}';
    return $this;
  }

  public function offsetExists(mixed $offset): bool {
    throw new \Exception("Checking existence of DocBlock as an array is not allowed.");
  }

  public function offsetGet(mixed $offset): mixed {
    throw new \Exception("Accessing DocBlock as an array is not allowed.");
  }

  public function offsetUnset(mixed $offset): void {
    throw new \Exception("Unsetting DocBlock as array with offsetUnset $offset.");
  }

  /**
   * Adds a new paragraph to the docblock text.
   *
   * The array offset has no effect.
   */
  public function offsetSet(mixed $offset, mixed $value): void {
    $this->paragraphs[] = $value;
  }

  /**
   * Adds a tag with the name of the called method.
   *
   * Arguments depend on the tag type:
   *  - param:
   *    - parameter name
   *    - parameter type (pass NULL if there is no type)
   *    - description (an empty value will get a default based on the parameter
   *      name)
   *    - by_reference (an empty value means FALSE)
   *  - return
   *    - return type (optional)
   *    - description
   *
   * For other tag types, the first argument is the optional description.
   */
  public function __call(string $name, array $arguments): mixed {
    $this->tags[$name][] = $arguments;

    return $this;
  }

  public function addAnnotation(ClassAnnotation $annotation) {
    $this->paragraphs[] = $annotation->render();
  }

  /**
   * Render the docblock into an array of code lines.
   *
   * @return array
   *   An array of lines.
   */
  public function render(): array {
    $lines = [];

    // An initial tag, such as '@file', goes before paragraphs and has no space
    // after it.
    if ($this->initialTag) {
      $lines[] = '@' . $this->initialTag;
    }

    $last_index = array_key_last($this->paragraphs);
    foreach ($this->paragraphs as $index => $paragraph) {
      if (is_array($paragraph)) {
        // A paragraph is already an array if it's imported from an annotation.
        $lines = array_merge($lines, $paragraph);
      }
      else {
        $lines = array_merge($lines, $this->wrapLine($paragraph, $this->getBodyIndentCount()));
      }

      if ($index != $last_index) {
        $lines[] = '';
      }
    }

    if (!empty($this->tags)) {
      // Make a copy we can alter.
      $tags = $this->tags;

      // Params.
      if (isset($tags['param'])) {
        $lines[] = '';

        foreach ($tags['param'] as $arguments) {
          list ($typehint, $param_name, $description) = $arguments;
          $by_reference = $arguments[3] ?? FALSE;

          $parameter_symbol =
            ($by_reference ? '&' : '')
            . '$'
            . $param_name;

          if (empty($typehint)) {
            $lines[] = '@param ' . $parameter_symbol;
          }
          else {
            $lines[] = '@param ' . $typehint . ' ' . $parameter_symbol;
          }

          if (empty($description)) {
            $description = CaseString::snake('The_' . $param_name)->sentence() . '.';
          }

          $tag_description_lines = $this->wrapLine($description, $this->getTagDescriptionIndentCount());
          $tag_description_lines = $this->indentCodeLines($tag_description_lines);

          $lines = array_merge($lines, $tag_description_lines);
        }

        unset($tags['param']);
      }

      // Return.
      if (isset($tags['return'])) {
        $arguments = $tags['return'][0];

        $lines[] = '';

        if (count($arguments) == 2) {
          $lines[] = '@return ' . $arguments[0];
        }
        else {
          $lines[] = '@return';
        }

        $tag_description = end($arguments);
        $tag_description_lines = $this->wrapLine($tag_description, $this->getTagDescriptionIndentCount());
        $tag_description_lines = $this->indentCodeLines($tag_description_lines);

        $lines = array_merge($lines, $tag_description_lines);

        unset($tags['return']);
      }

      // Other tags.
      foreach ($tags as $tag_name => $tag_items) {
        // Groups of tags must have a line between them: Coder sniff
        // Drupal.Commenting.DocComment.TagGroupSpacing.
        $lines[] = '';

        foreach ($tag_items as $tag_item) {
          $lines[] = '@' . $tag_name . (isset($tag_item[0]) ? ' ' . $tag_item[0] : '');

          if (!empty($tag_item[1])) {
            $tag_description_lines = $this->wrapLine($tag_item[1], $this->getTagDescriptionIndentCount());
            $tag_description_lines = $this->indentCodeLines($tag_description_lines);

            $lines = array_merge($lines, $tag_description_lines);
          }
        }
      }
    }

    $lines = $this->docBlock($lines);

    return $lines;
  }

  /**
   * Creates a PhpParser comment node for this docblock.
   *
   * @return \PhpParser\Comment
   *   The parser node. This can be pretty-printed to render the docblock.
   */
  public function toParserCommentNode(): Comment {
    return new Comment(
      // Comments expect a single string rather than lnes.
      implode("\n", $this->render())
    );
  }

  /**
   * Calculates the total indent count for body text, for text wrapping.
   *
   * @return int
   *   The number of characters to the left of the body text.
   */
  protected function getBodyIndentCount(): int {
    return
      // Class or function code indent.
      (2 * $this->indentLevel)
      // Space before the doc comment asterisk, the asterisk, and space
      // after.
      + 3;
  }

  /**
   * Calculates the total indent count for tag descriptions, for text wrapping.
   *
   * @return int
   *   The number of characters to the left of the body text.
   */
  protected function getTagDescriptionIndentCount() {
    return
      // Class or function code indent.
      2 * $this->indentLevel
      // Space and the doc comment asterisk.
      + 2
      // Indentation for the parameter description.
      + 3;
  }

  /**
   * Wraps a line to the specified width.
   *
   * @param string $line
   *   The line of text to wrap.
   * @param int $indent_count
   *   The number of characters that this line will be indented by.
   *
   * @return array
   *   An array of lines.
   */
  protected function wrapLine(string $line, int $indent_count): array {
    // Wrap the description to 80 characters minus the indentation.
    $wrapped_line = wordwrap($line, 80 - $indent_count);
    $wrapped_lines = explode("\n", $wrapped_line);
    return $wrapped_lines;
  }

  /**
   * Formats lines of text as a docblock.
   *
   * @param @lines
   *  An array of lines. Lines to be normally indented should have no leading
   *  whitespace.
   *
   * @return
   *  An array of lines for the docblock with start and end PHP comment markers.
   */
  function docBlock(array $lines): array {
    $lines = array_merge(
      ["/**"],
      array_map(function ($line) {
        if (empty($line)) {
          return ' *';
        }
        return " * $line";
      }, $lines),
      [" */"]
    );

    return $lines;
  }

 /**
   * Indent all the non-empty lines in a block of code by 2 spaces.
   *
   * @param array $lines
   *   An array of code lines.
   *
   * @return
   *   The array of code lines with the indentation applied.
   */
  function indentCodeLines(array $lines) {
    $indent = '  ';

    $indented_lines = array_map(function ($line) use ($indent) {
      return empty($line) ? $line : $indent . $line;
    }, $lines);
    return $indented_lines;
  }

}
