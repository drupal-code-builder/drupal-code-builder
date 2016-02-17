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

  /**
   * Constructor method; sets the component data.
   *
   * The component name is taken to be the fully-qualified class name.
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    parent::__construct($component_name, $component_data, $generate_task, $root_generator);

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
  public function getFileInfo() {
    $files['src/' . $this->qualified_class_name] = array(
      'path' => $this->path,
      'filename' => $this->plain_class_name . '.php',
      'body' => $this->file_contents(),
      'join_string' => "\n",
    );
    return $files;
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
   * Return the summary line for the file docblock.
   */
  function file_doc_summary() {
    return "Contains $this->qualified_class_name.";
  }

  /**
   * Return the main body of the file code.
   */
  function code_body() {
    $return = array_merge([
        'namespace ' . $this->namespace,
        '',
        $this->docBlock("TODO: class docs."),
        "class $this->plain_class_name {",
      ],
      $this->class_code_body(),
      [
        '}',
      ]);
    return $return;
  }

  /**
   * Return the body of the class's code.
   */
  protected function class_code_body() {
    return [
      '',
    ];
  }

}
