<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\PHPClassFile.
 */

namespace DrupalCodeBuilder\Generator;

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

    // The component name is the fully-qualified class name.
    $this->setClassNames($this->name);
  }

  /**
   * Set properties relating to class name.
   *
   * This is called by this class's constructor. Child classes should override
   * this and call the parent method to change the parameter it receives.
   *
   * @param $qualified_class_name
   *  The fully-qualified class name, e.g. 'Drupal\\foo\\Bar\\Classname'.
   */
  protected function setClassNames($qualified_class_name) {
    $this->qualified_class_name = $qualified_class_name;
    $pieces = explode('\\', $this->qualified_class_name);
    $this->plain_class_name = array_pop($pieces);
    $this->namespace  = implode('\\', $pieces);
    $path_pieces = array_slice($pieces, 2);
    array_unshift($path_pieces, 'src');
    $this->path       = implode('/', $path_pieces);
  }

  /**
   * Build the code files.
   *
   * Subclasses should override this to add their file data to the list.
   */
  public function getFileInfo() {
    $files[$this->path . '/' . $this->plain_class_name . '.php'] = array(
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
   * Helper for subclasses. Serves to concatenate standard pieces of the file.
   *
   * @return
   *  An array of text strings, in the correct order for concatenation.
   */
  function file_contents() {
    // File contents are built up.
    $file_contents = array_merge(
      array(
        $this->file_header(),
      ),
      // The code header and body are themselves arrays.
      $this->code_header(),
      $this->code_body(),
      array(
        $this->code_footer(),
      )
    );

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
    $return = array_merge(
      $this->code_namespace(),
      $this->imports(),
      $this->docBlock("TODO: class docs."),
      [
        "class $this->plain_class_name {",
      ],
      $this->class_code_body(),
      [
        '}',
      ]);
    return $return;
  }

  /**
   * Produces the namespace and 'use' lines.
   */
  function code_namespace() {
    $code = array();

    $code[] = 'namespace ' . $this->namespace . ';';
    $code[] = '';

    return $code;
  }

  /**
   * Produces the namespace import statements.
   */
  function imports() {
    $imports = [];
    // TODO!!! is there any way to figure these out??
    $imports[] = '// use yadayada;';
    $imports[] = '';
    return $imports;
  }

  /**
   * Return the body of the class's code.
   */
  protected function class_code_body() {
    $code_body = array();

    // Blank line before the first method.
    $code_body[] = '';

    // Function data has been set by buildComponentContents().
    foreach ($this->functions as $component_name => $function_lines) {
      // Add extra indent for methods.
      $function_lines = array_map(function($line) {
        return empty($line) ? $line : '  ' . $line;
      }, $function_lines);

      $code_body = array_merge($code_body, $function_lines);
      // Blank line after the function.
      $code_body[] = '';
    }

    return $code_body;
  }

}
