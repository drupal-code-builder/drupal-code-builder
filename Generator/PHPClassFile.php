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
    $return = array_merge([
        'namespace ' . $this->namespace,
        '',
      ],
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
