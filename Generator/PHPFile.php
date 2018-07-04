<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;

/**
 * Generator for general PHP code files.
 *
 * Code files for modules, theme, etc, should inherit from this.
 */
class PHPFile extends File {

  use PHPFormattingTrait;
  use NameFormattingTrait;

  /**
   * An array of functions for this file.
   *
   * @see buildComponentContents()
   * @see code_body()
   */
  protected $functions = array();

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // TEMPORARY, until Generate task handles returned contents.
    $this->functions = $this->filterComponentContentsForRole($children_contents, 'function');

    return array();
  }

  /**
   * Return the contents of the file.
   *
   * Helper for subclasses. Serves to concatenate standard pieces of the file.
   *
   * @return
   *  An array of text strings, in the correct order for concatenation.
   */
  protected function fileContents() {
    // If only bare code is requested, only output the body, wthout headers
    // or footer.
    /*
    // TODO: Decide whether to restore this or remove it.
    $module_data = $this->root_component->component_data;
    if (!empty($module_data['bare_code'])) {
      return $this->code_body();
    }
    */

    // File contents are built up.
    $file_contents = array_merge(
      $this->file_header(),
      // The code header and body are themselves arrays.
      $this->code_header(),
      $this->code_body()
    );

    if (!empty($this->code_footer())) {
      $file_contents[] = $this->code_footer();
    }

    return $file_contents;
  }

  /**
   * Return the PHP file header lines.
   */
   function file_header()  {
     return [
       "<?php",
       '',
     ];
   }

  /**
   * Return the file doxygen header and any custom header code.
   */
  function code_header() {
    $lines = array(
      "@file",
      $this->fileDocblockSummary(),
    );
    $code = $this->docBlock($lines);
    // Blank line after the file docblock.
    $code[] = '';
    return $code;
  }

  /**
   * Return the main body of the file code.
   *
   * @return
   *  An array of code lines.
   */
  function code_body() {
    $code_body = array();

    // Function data has been set by buildComponentContents().
    foreach ($this->functions as $component_name => $function_lines) {
      $code_body = array_merge($code_body, $function_lines);
      // Blank line after the function.
      $code_body[] = '';
    }

    // If there are no functions, then this is a .module file that's been
    // requested so the module is correctly formed. It is customary to add a
    // comment to the file for DX.
    if (empty($code_body)) {
      $code_body['empty'] = "// Drupal needs this blank file.";
      $code_body[] = '';
    }

    // Replace any fully-qualified classes with short class names, and keep a
    // list of the replacements to make import statements with.
    $imported_classes = [];
    $this->extractFullyQualifiedClasses($code_body, $imported_classes);

    $return = array_merge(
      $this->imports($imported_classes),
      $code_body
    );
    return $return;
  }

  /**
   * Remove fully-qualified classnames, extracting them to an array.
   *
   * @param &$class_code
   *  An array of PHP code lines to work on. All namespaced classes will be
   *  replaced with plain classes.
   * @param &$imported_classes
   *  An array to populate with the fully-qualified classnames which are
   *  removed. These are without the initial namespace separator.
   * @param string $current_namespace
   *  (optional) The namespace of the current file, without the initial '\'. If
   *  omitted, no comparison of namespace is done.
   */
  protected function extractFullyQualifiedClasses(&$class_code, &$imported_classes, $current_namespace = '') {
    $current_namespace_pieces = explode('\\', $current_namespace);

    foreach ($class_code as &$line) {
      // Skip lines which are part of a comment block.
      if (preg_match('@^\s*\*@', $line)) {
        continue;
      }
      // Skip lines which are a single comment.
      if (preg_match('@^\s*//@', $line)) {
        continue;
      }
      // Skip PHPStorm variable typehints.
      if (preg_match('@^\s*/\*\*@', $line)) {
        continue;
      }

      $matches = [];
      // Do not match after a ' or ", as then the class name is a quoted string
      // and should be left alone.
      // Do not match after a letter, as then that's also part of the namespace
      // and we shouldn't be matching only the tail end.
      // Allow a match of '%module' for a namespace piece, for cases where we
      // refer to something we are also in the process of generating, e.g. a
      // plugin base class referencing a plugin interface.
      if (preg_match_all('@(?<![\'"[:alpha:]])(?:\\\\(\w+|%module)){2,}@', $line, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match_set) {
          $fully_qualified_class_name = $match_set[0];
          $class_name = $match_set[1];
          $line = preg_replace('@' . preg_quote($fully_qualified_class_name) . '@', $class_name, $line);

          $fully_qualified_class_name = ltrim($fully_qualified_class_name, '\\');
          $namespace_pieces = array_slice(explode('\\', $fully_qualified_class_name), 0, -1);

          if ($namespace_pieces != $current_namespace_pieces) {
            $imported_classes[] = ltrim($fully_qualified_class_name, '\\');
          }
        }
      }
    }

    // Remove duplicates.
    $imported_classes = array_unique($imported_classes);
  }

  /**
   * Produces the namespace import statements.
   *
   * @param $imported_classes
   *  (optional) An array of fully-qualified class names.
   */
  function imports($imported_classes = []) {
    $imports = [];

    if ($imported_classes) {
      // Sort the imported classes.
      sort($imported_classes);

      foreach ($imported_classes as $fully_qualified_class_name) {
        $fully_qualified_class_name = ltrim($fully_qualified_class_name, '\\');
        $imports[] = "use $fully_qualified_class_name;";
      }

      $imports[] = '';
    }

    return $imports;
  }

  /**
   * Returns the summary line for the file docblock.
   *
   * @return
   *   The text to go after the @file tag in the file's docblock.
   */
  function fileDocblockSummary() {
    return "TODO: Enter file description here.";
  }

  /**
   * Return a file footer.
   */
  function code_footer() {}

}
