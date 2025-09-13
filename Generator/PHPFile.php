<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;
use DrupalCodeBuilder\Generator\Render\DocBlock;

/**
 * Generator for general PHP code files.
 *
 * Code files for modules, theme, etc, should inherit from this.
 */
abstract class PHPFile extends File {

  use PHPFormattingTrait;
  use NameFormattingTrait;

  /**
   * An array of functions for this file.
   *
   * TODO: Remove this.
   *
   * @see code_body()
   */
  protected $functions = [];

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
      return $this->phpCodeBody();
    }
    */

    // File contents are built up.
    $file_contents = array_merge(
      $this->fileHeader(),
      // The code header and body are themselves arrays.
      $this->codeHeader(),
      $this->phpCodeBody()
    );

    if (!empty($this->codeFooter())) {
      $file_contents[] = $this->codeFooter();
    }

    return $file_contents;
  }

  /**
   * Return the PHP file header lines.
   */
  function fileHeader()  {
    // Don't use strict_types here, as some PHP files such as API files don't
    // use it, and ExtensionCodeFile needs special handling.
     return [
       "<?php",
       '',
     ];
   }

  /**
   * Return the file doxygen header and any custom header code.
   */
  function codeHeader() {
    $docblock = DocBlock::file();

    $docblock[] = $this->fileDocblockSummary();

    $code = $docblock->render();
    // Blank line after the file docblock.
    $code[] = '';
    return $code;
  }

  /**
   * Return the main body of the file code.
   *
   * This is everything after the opening '<php' tag.
   *
   * @return
   *  An array of code lines. Keys are immaterial but should avoid clashing.
   */
  abstract function phpCodeBody();

  /**
   * Remove fully-qualified classnames, extracting them to an array.
   *
   * It is assummed that %-style tokens are *not* used in the fully qualified
   * classname, as the extracted values will be used for sorting the imports
   * section of the file; @see self::imports().
   *
   * @param &$class_code
   *  An array of PHP code lines to work on. All namespaced classes will be
   *  replaced with plain classes or aliases.
   * @param &$imported_classes
   *  An array to populate with the list of fully-qualified classnames which
   *  have been replaced with short classes in the class code. Keys are
   *  fully-qualified classnames without the initial namespace separator. Values
   *  are either the class alias used to replace the full class, or NULL if the
   *  short class was used as a replacement.
   * @param string $current_namespace
   *  (optional) The namespace of the current file, without the initial '\'. If
   *  omitted, no comparison of namespace is done.
   */
  protected function extractFullyQualifiedClasses(&$class_code, &$imported_classes, $current_namespace = '') {
    $current_namespace_pieces = explode('\\', $current_namespace);

    // An array of replacements to make in the entire class code once all names
    // have been extraced and clashes resolved. The search values are the full
    // class names with markers surrounding them, to prevent inadvertent
    // replacement.
    $replacements = [];
    // An array of the classes that are to be replaced. Keys are the full class
    // name and value are the short class name. This is used to detect short
    // name clashes.
    $replaced_classes = [];

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

      // Replacements to make in the current line.
      $line_marker_replacements = [];

      $matches = [];
      // Do not match after a ' or ", as then the class name is a quoted string
      // and should be left alone.
      // Do not match after a letter or number, as then that's also part of the
      // namespace and we shouldn't be matching only the tail end.
      // Match any number of sequences '\Portion', where 'Portion' can contain
      // a '%' symbol for tokens.
      if (preg_match_all('@(?<![\'"[:alnum:]])(?:\\\\([\w%]+)){2,}@', $line, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match_set) {
          $fully_qualified_class_name = $match_set[0];
          $class_name = $match_set[1];

          // Form a replacement string, which uses surrounding markers to ensure
          // that we don't replace the class name in comments or typehints.
          $marker_wrapped_search_string = '@IMPORT' . $fully_qualified_class_name . 'IMPORT@';

          // Build an array of replacements for the line, so we do all the
          // replacements in one go. This prevents re-wrapping a repeated class
          // with the markers.
          $line_marker_replacements[$fully_qualified_class_name] = $marker_wrapped_search_string;

          // Add the marker-wrapped name to the list of all replacements, and
          // the full class name to the list of all classes.
          $replacements[$marker_wrapped_search_string] = $class_name;
          $replaced_classes[$fully_qualified_class_name] = $class_name;

          $namespace_pieces = array_slice(explode('\\', $fully_qualified_class_name), 1, -1);

          // If the class isn't in the current namespace, then add it to the
          // list of imported classes. We don't yet trim the leading '\' so
          // that we can compare the keys when resolving clashes.
          if ($namespace_pieces != $current_namespace_pieces) {
            $imported_classes[$fully_qualified_class_name] = NULL;
          }
        } // foreach matches

        $line = str_replace(array_keys($line_marker_replacements), array_values($line_marker_replacements), $line);
      }
    } // foreach line

    // Resolve any clashes in short class names. To use an alias instead of the
    // short class name, we:
    // - replace it in the $replacements array
    // - set it as a value in the $imported_classes array.
    $clashes = [];
    foreach ($replaced_classes as $full_class => $short_class) {
      $clashes[$short_class][] = $full_class;
    }
    $clashes = array_filter($clashes, fn ($array) => count($array) > 1);

    $component_namespace = '\Drupal\\' . $this->component_data->root_component_name->value;

    foreach ($clashes as $short_class => $clash_set) {
      // Rule 1: A non-Drupal class loses out to the Drupal class, and gets an
      // alias with a prefix of its top-level namespace.
      foreach ($clash_set as $full_class) {
        if (!str_starts_with($full_class, '\Drupal')) {
          $pieces = explode('\\', $full_class);
          $alias = $pieces[1] . $short_class;
          // ARGH! need to remake the marker-wrapped name!
          $replacements['@IMPORT' . $full_class . 'IMPORT@'] = $alias;

          // Set the alias into the list of imported classes.
          $imported_classes[$full_class] = $alias;
        }
      }

      // Rule 2: if multiple classes belong to the current module, prefix each
      // one with the immediate parent namespace.
      $clash_set_clashes_in_current_component = array_filter($clash_set, fn ($full_class) => str_starts_with($full_class, $component_namespace));
      if (count($clash_set_clashes_in_current_component) > 1) {
        foreach ($clash_set_clashes_in_current_component as $full_class) {
          $pieces = explode('\\', $full_class);
          $alias = implode('', array_slice($pieces, -2));

          $replacements['@IMPORT' . $full_class . 'IMPORT@'] = $alias;
          $imported_classes[$full_class] = $alias;
        }
      }
    }

    // Replace the marker-wrapped full classes with the short classes in the
    // whole code.
    $class_code = str_replace(array_keys($replacements), array_values($replacements), $class_code);

    // Trim the initial '\' from the list of imported classes.
    $new_keys = array_map(fn ($full_class) => ltrim($full_class, '\\'), array_keys($imported_classes));
    $imported_classes = array_combine($new_keys, array_values($imported_classes));
  }

  /**
   * Produces the namespace import statements.
   *
   * @param $imported_classes
   *  An array of fully-qualified class names and aliases. Keys are fully-qualified class names,
   *  either with or without the leading slash. Values are one of:
   *  - NULL to indicate there is no alias.
   *  - The class name alias to use.
   */
  function imports($imported_classes) {
    $import_lines = [];

    if ($imported_classes) {
      foreach ($imported_classes as $fully_qualified_class_name => $alias) {
        $fully_qualified_class_name = ltrim($fully_qualified_class_name, '\\');
        $import_lines[] = "use $fully_qualified_class_name" . ($alias ? " as $alias" : '') . ';';
      }

      // Bit of a hack. We have to perform token replacement before sorting the
      // imports, because otherwise they'll be in the wrong order. But token
      // replacement is done later, during file assembly. Fortunately, in
      // class names we can be certain that only the %extension and %Pascal
      // tokens are used, so hackily replace those now.
      $import_lines = str_replace(
        [
          '%extension',
          '%Pascal'
        ],
        [
          $this->component_data->root_component_name->value,
          CaseString::snake($this->component_data->root_component_name->value)->pascal(),
        ],
        $import_lines,
      );

      // Sort the imported classes.
      natcasesort($import_lines);

      $import_lines[] = '';
    }

    return $import_lines;
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
   * Returns a file footer.
   *
   * This is only used if non-empty.
   */
  function codeFooter() {}

}
