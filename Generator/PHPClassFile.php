<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\PHPClassFile.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for PHP class files.
 */
class PHPClassFile extends PHPFile {

  function __construct($component_name, $component_data = array()) {
    parent::__construct($component_name, $component_data);

    $this->setClassNames($this->name);
  }

  /**
   * Set properties relating to class name.
   */
  protected function setClassNames($component_name) {
    // By default, take the component name to be the fully-qualified class name.
    $this->qualified_class_name = $component_name;
    $pieces = explode('\\', $this->qualified_class_name);
    $this->plain_class_name = array_pop($pieces);
    $this->namespace  = implode('\\', $pieces);
    $this->path       = implode('/', $pieces);
  }

  /**
   * Build the code files.
   *
   * Subclasses should override this to add their file data to the list.
   */
  function collectFiles(&$files) {
    $files[$this->qualified_class_name] = array(
      'path' => $this->path,
      'filename' => $this->plain_class_name . '.php',
      'body' => $this->file_contents(),
      // We join the info lines with linebreaks, as they (currently!) do not
      // come with their own lineends.
      // TODO: fix this!
      'join_string' => "\n",
    );
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
    // TODO: ARGH!
    //$file_contents = array_filter($file_contents);
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
    $code = <<<EOT
/**
 * @file
 * Contains $this->qualified_class_name.
 */

EOT;
    return $code;
  }

  /**
   * Return the main body of the file code.
   */
  function code_body() {
    return [
      'namespace ' . $this->namespace,
      '',
      $this->docBlock("TODO: class docs."),
      "class $this->plain_class_name {",
      '',
      '}',
    ];
  }

}
