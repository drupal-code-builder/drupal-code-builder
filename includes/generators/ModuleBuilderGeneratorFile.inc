<?php

/**
 * @file
 * Contains generator classes for module code files.
 */

/**
 * Abstract Generator base class for files. TODO: rename? merge into Code?
 */
abstract class ModuleBuilderGeneratorFile extends ModuleBuilderGeneratorComponent {

  /**
   * Return an empty array of subcomponent types.
   *
   * Files are always terminal components.
   */
  protected function subComponents() {
    return array();
  }

  /**
   * Return the contents of the file.
   *
   * @return
   *  An array of text strings, in the correct order for concatenation.
   */
  function file_contents() {
    // If only bare code is requested, only output the body, wthout headers
    // or footer.
    $module_data = $this->component_data;
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
    $default = <<<EOT
/**
 * @file $filename
 * $file_description
 */

EOT;
    $code = variable_get('module_builder_header', $default);
    return $code;
  }

  /**
   * Return the summary line for the file docblock.
   */
  function file_doc_summary() {
    return "TODO: Enter file description here.";
  }

  /**
   * Return the main body of the file code.
   *
   * @return
   *  An array of chunks of text for the code body.
   */
  abstract function code_body();

  /**
   * Return a file footer.
   */
  function code_footer() {}

}
