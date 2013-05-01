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
  function subComponents() {
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
      return array(
        $this->code_body(),
      );
    }

    return array(
      $this->file_header(),
      $this->code_header(),
      $this->code_body(),
      $this->code_footer(),
    );
  }

  /**
   * Return the PHP file header line.
   */
   function file_header()  {
     return "<?php\n";
   }

  /**
   * Return the file doxygen header and any custom header code.
   */
  function code_header() {
    $filename = $this->filename;
    $default = <<<EOT
/**
 * @file $filename
 * TODO: Enter file description here.
 */

EOT;
    $code = variable_get('module_builder_header', $default);
    return $code;
  }

  /**
   * Return the main body of the file code.
   */
  abstract function code_body();

  /**
   * Return a file footer.
   */
  function code_footer() {}

}
