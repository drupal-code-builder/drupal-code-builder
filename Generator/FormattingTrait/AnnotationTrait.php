<?php

namespace DrupalCodeBuilder\Generator\FormattingTrait;

/**
 * Trait for creating annotations.
 */
trait AnnotationTrait {

  /**
   * Creates class annotation lines from a data array.
   *
   * @param array $annotation_data
   *   An array of data for the annotation, containing:
   *   - '#class': The name of the annotation class.
   *   - '#data': An array of annotation data. Keys are keys for the annotation
   *      data, while values may be one of the following formats:
   *      - a string, which produces a plain quoted value.
   *      - an array in the same format as this parameter, to nest another
   *        annotation.
   *      - an array with keys '#class' and '#data', where '#data' is a string
   *        value, to produce a single-value and single-line nested annotation.
   *
   * @return
   *   An array of lines that should be passed to docblock(). Other lines can
   *   be merged in, such as a class summary or documentation.
   */
  function renderAnnnotation($annotation_data, $indent = 1) {
    $docblock_lines = [];

    // First line for the annotation key / class.
    $docblock_lines[] = "@{$annotation_data['#class']}(";

    foreach ($annotation_data['#data'] as $key => $value) {
      if (is_array($value) && isset($value['#class'])) {
        // Nested annotation with a class.
        if (is_array($value['#data'])) {
          // Child array: recurse.
          $child_docblock_lines = $this->renderAnnnotation($value, $indent + 1);

          // Tack the key at the front with an indent.
          $child_docblock_lines[0] = str_repeat('  ', $indent) . "$key = " . $child_docblock_lines[0];

          // Redo the last line to be intended and have a comma.
          array_pop($child_docblock_lines);
          $child_docblock_lines[] = str_repeat('  ', $indent) . '),';

          $docblock_lines = array_merge($docblock_lines, $child_docblock_lines);
        }
        else {
          // Child scalar value.
          $docblock_lines[] = str_repeat('  ', $indent)
            . $key
            . ' = '
            . "@{$value['#class']}(\"" . $value['#data'] . '"),';
        }
      }
      else {
        // Nested array values.
        // On the first call to these, the keys are properties of the annotation
        // class, and do not get quoted.
        $this->annotationLineProcessor($key, $value, $indent, $docblock_lines, FALSE);
      }
    }

    $docblock_lines[] = ")";

    return $docblock_lines;
  }

  /**
   * Helper for annotation() to process a single data item.
   *
   * @param $key
   *   The key from the data array.
   * @param $value
   *   The value from the data array.
   * @param $indent
   *   The current indent value, as a multiplier of two spaces.
   * @param &$docblock_lines
   *   The array of docblock lines being built up.
   * @param $nesting
   *   (optional) Whether this method is being called recursively. This is used
   *   to determine whether the key is the top level of an annotation class.
   */
  function annotationLineProcessor($key, $value, $indent, &$docblock_lines, $nesting) {
    // Only top level keys of an annotation class are bare; after that, they
    // must be quoted as strings.
    if ($nesting) {
      $key = '"' . $key . '"';
    }

    if (is_array($value)) {
      $docblock_lines[] = str_repeat('  ', $indent) . "{$key} = {";

      foreach ($value as $inner_key => $inner_value) {
        $this->annotationLineProcessor($inner_key, $inner_value, $indent + 1, $docblock_lines, TRUE);
      }

      $docblock_lines[] = str_repeat('  ', $indent) . "},";
    }
    else {
      if (is_bool($value)) {
        $value = $value ? 'TRUE' : 'FALSE';
      }
      else {
        // Quote the value.
        $value = '"' . $value . '"';
      }

      $docblock_lines[] = str_repeat('  ', $indent)
        . (
          is_numeric($key)
          ? ''
          : $key . ' = '
        )
      . $value
      . ',';
    }
  }

}
