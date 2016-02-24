<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\PHPFile.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for general PHP code files.
 *
 * Code files for modules, theme, etc, should inherit from this.
 */
class PHPFile extends File {

  use PHPFormattingTrait;

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
  function buildComponentContents($children_contents) {
    // TEMPORARY, until Generate task handles returned contents.
    $this->functions = $children_contents;

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
  function file_contents() {
    // If only bare code is requested, only output the body, wthout headers
    // or footer.
    $module_data = $this->base_component->component_data;
    if (!empty($module_data['bare_code'])) {
      return $this->code_body();
    }

    // File contents are built up.
    $file_contents = array_merge(
      array(
        $this->file_header(),
      ),
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
   * Return the PHP file header line.
   */
   function file_header()  {
     return "<?php\n";
   }

  /**
   * Return the file doxygen header and any custom header code.
   *
   * Expects $this->filename to be set.
   */
  function code_header() {
    $lines = array(
      "@file",
      $this->file_doc_summary(),
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

    return $code_body;
  }

  /**
   * Return the summary line for the file docblock.
   */
  function file_doc_summary() {
    return "TODO: Enter file description here.";
  }

  /**
   * Return a file footer.
   */
  function code_footer() {}

}
