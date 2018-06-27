<?php

namespace DrupalCodeBuilder\Generator\Render;

/**
 * Renderer for a docblock class annotation.
 *
 * This allows code for class annotations to be created with a mininum of
 * changes from the code that is to be output. The design goal here is that you
 * should be able to look at a sample of the code that you want to generate, and
 * as near as possible copy it into a generator class. This makes coding and
 * maintenance easier.
 *
 * The annotation rendered object is created with a static call to a method with
 * the name of the annotation class, with the annotation data as the  parameter.
 * Nested annotations are done in the same way. Calling render() on the
 * resulting object produces the lines of code suitable to be passed to the
 * docblock renderer. (Rendering without the comment formatting allows other
 * documentation to be added to the docblock.)
 *
 * @code
 * // The main annotation class is ContentEntityType.
 * $annotation = ClassAnnotation::ContentEntityType([
 *   'id' => 'cat',
 *   // A string child annotation with class Translation.
 *   'label' => ClassAnnotation::Translation('Cat'),
 *   // An array child annotation with class PluralTranslation.
 *   'label_count' => ClassAnnotation::PluralTranslation([
 *     'singular' => "@count content item",
 *     'plural' => "@count content items",
 *   ],
 * ]);
 * $annotation_lines = $annotation->render();
 * @endcode
 */
class ClassAnnotation {

  /**
   * The name of the class for this annotation.
   *
   * @var string
   */
  protected $annotationClassName;

  /**
   * The data for this annotation.
   *
   * @var mixed
   */
  protected $data;

  /**
   * Constructor.
   *
   * Note that this class must not be instantiated with 'new', only with
   * magic static calls. See __callStatic().
   *
   * @param string $annotation_class_name
   *   The name of the annotation class.
   * @param mixed $data
   *   The data for the annotation.
   */
  public function __construct($annotation_class_name, $data) {
    $this->annotationClassName = $annotation_class_name;
    $this->data = $data;
  }

  /**
   * Magic method: creates a new annotation.
   *
   * This allows annotations to be nested with a simple syntax that looks very
   * similar to the actual annotation code to output.
   *
   * @param string $annotation_class_name
   *   The name of the annotation class.
   * @param mixed $data
   *   The data for the annotation.
   *
   * @return self
   *   An instance of this class which can be rendered.
   */
  public static function __callStatic($annotation_class_name, $parameters) {
    return new static($annotation_class_name, $parameters[0]);
  }

  /**
   * Renders the annotation as text lines without docblock formatting.
   *
   * @param int $nesting
   *   (optional) Internal. The current nesting level of the overal data.
   * @param int $annotation_nesting
   *   (optional) Internal. The current nesting level in the current annotation.
   *
   * @return string[]
   *   The rendered lines of code.
   */
  public function render($nesting = 0, $annotation_nesting = 0) {
    $docblock_lines = [];

    $indent = str_repeat('  ', $nesting);

    // Simple string annotation.
    if (is_string($this->data)) {
      // Quote the value.
      $value = '"' . $this->data . '"';

      // If this is the top-level annotation, then the whole thing is a single
      // line and there is no terminal comma.
      $docblock_lines[] = "@{$this->annotationClassName}({$value})" . ($nesting ? ',' : '');
    }
    else {
      // First line for the annotation key / class.
      $docblock_lines[] = "@{$this->annotationClassName}(";

      // Render the array of data recursively, including any nested annotations.
      $this->renderArray($docblock_lines, $this->data, $nesting + 1, $annotation_nesting);

      $docblock_lines[] = $indent . ")" . ($nesting ? ',' : '');
    }

    return $docblock_lines;
  }

  /**
   * Add lines for an array of data.
   *
   * @param string[] &$docblock_lines
   *   The lines of code assembled so far. Further lines are added to this.
   * @param mixed $data
   *   The array of data.
   * @param int $nesting
   *   (optional) See render().
   * @param int $annotation_nesting
   *   (optional) See render().
   */
  protected function renderArray(&$docblock_lines, $data, $nesting = 0, $annotation_nesting = 0) {
    $indent = str_repeat('  ', $nesting);

    foreach ($data as $key => $value) {
      if (is_numeric($key)) {
        // Numeric keys are not shown.
        $declaration_line = "{$indent}";
      }
      else {
        // Keys need to be quoted for all levels except the first level of an
        // annotation.
        if ($annotation_nesting != 0) {
          $key = '"' . $key . '"';
        }

        $declaration_line = "{$indent}{$key} = ";
      }

      if (is_string($value)) {
        $value = '"' . $value . '"';

        $declaration_line .= "{$value},";
        $docblock_lines[] = $declaration_line;
      }
      elseif (is_object($value)) {
        // Child annotation. The nesting level doesn't increase here, as the
        // child annotation class is just on the same line as the key.
        // The annotation nesting level is reset, as we are starting a new
        // annotation.
        $sub_lines = $value->render($nesting, 0);

        $declaration_line .= array_shift($sub_lines);
        $docblock_lines[] = $declaration_line;

        $docblock_lines = array_merge($docblock_lines, $sub_lines);
      }
      else {
        // Array of values. Recurse into this method.
        $declaration_line .= '{';
        $docblock_lines[] = $declaration_line;

        $this->renderArray($docblock_lines, $value, $nesting + 1, $annotation_nesting + 1);

        $docblock_lines[] = $indent . "},";
      }
    }

  }

}
