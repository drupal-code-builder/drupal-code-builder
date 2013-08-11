<?php

/**
 * @file
 * Contains generator classes for module code files.
 */

namespace ModuleBuider\Generator;

/**
 * Abstract Generator base class for files. TODO: rename? merge into Code?
 */
abstract class File extends Base {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   *
   * A File generator should use as its name the generic form of its eventual
   * file name, that is, the filename with the token '%module' in place of the
   * module name. For example: %module.module, $module.views.inc, etc.
   */
  public $name;

  /**
   * The actual name of the file to write.
   */
  protected $filename;

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
