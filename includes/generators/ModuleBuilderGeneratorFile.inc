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
