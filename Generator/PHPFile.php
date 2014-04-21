<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\PHPFile.
 */

namespace ModuleBuider\Generator;

/**
 * Generator for general PHP code files.
 *
 * Code files for modules, theme, etc, should inherit from this.
 */
class PHPFile extends File {

  /**
   * An array of functions for this file.
   *
   * @see assembleContainedComponentsHelper()
   * @see code_body()
   */
  protected $functions = array();

  /**
   * Helper for assembleContainedComponents().
   *
   * Module code files assemble their contained components, which are functions.
   *
   * This collects data from our contained components. The functions are
   * assembled in full in code_body().
   */
  function assembleContainedComponentsHelper($children) {
    $component_list = $this->getComponentList();

    foreach ($children as $child_name) {
      // Get the child component.
      $child_component = $component_list[$child_name];

      $child_functions = $child_component->componentFunctions();
      // Why didn't array_merge() work here? Cookie for the answer!
      $this->functions += $child_functions;
    }
  }

  /**
   * Return the contents of the file.
   *
   * Helper for subclasses' implementations of collectFiles(). Serves to
   * concatenate standard pieces of the file.
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
        $this->code_header(),
      ),
      // The code body is itself an array.
      $this->code_body(),
      array(
        $this->code_footer(),
      )
    );

    // Filter out any empty elements.
    $file_contents = array_filter($file_contents);
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
    $filename = $this->filename;
    $file_description = $this->file_doc_summary();
    $code = <<<EOT
/**
 * @file $filename
 * $file_description
 */

EOT;
    return $code;
  }

  /**
   * Return the summary line for the file docblock.
   */
  function file_doc_summary() {
    return "TODO: Enter file description here.";
  }

  /**
   * Create a doxygen block for a function.
   *
   * @param $text
   *  The first line of text for the doxygen block.
   */
  function function_doxygen($text) {
    return <<<EOT
/**
 * $text
 */

EOT;
  }

  /**
   * Return a file footer.
   */
  function code_footer() {}

}
