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
   * Properties in $component_data:
   *  - 'qualified_class_name': The fully-qualified class name.
   */
  function __construct($component_name, $component_data, $root_generator) {
    parent::__construct($component_name, $component_data, $root_generator);

    $this->setClassNames($component_data['qualified_class_name']);
  }

  /**
   * Set properties relating to class name.
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
      $this->file_header(),
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
    // Get the class code from the class docblock onwards first, so it can be
    // then processed for qualified class names.
    $class_code = array_merge(
      $this->class_doc_block(),
      $this->class_declaration(),
      $this->class_code_body()
    );

    // Replace any fully-qualified classes with short class names, and keep a
    // list of the replacements to make import statements with.
    $imported_classes = [];
    foreach ($class_code as &$line) {
      $matches = [];
      if (preg_match_all('@(?:\\\\(\w+))+@', $line, $matches, PREG_SET_ORDER) && !preg_match('@^\s*\*@', $line)) {
        foreach ($matches as $match_set) {
          $fully_qualified_class_name = $match_set[0];
          $class_name = $match_set[1];
          $line = preg_replace('@' . preg_quote($fully_qualified_class_name) . '@', $class_name, $line);

          $imported_classes[] = ltrim($fully_qualified_class_name, '\\');
        }
      }
    }

    $return = array_merge(
      $this->code_namespace(),
      $this->imports($imported_classes),
      $class_code,
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
   *
   * @param $imported_classes
   *  (optional) An array of fully-qualified class names.
   */
  function imports($imported_classes = []) {
    $imports = [];

    if ($imported_classes) {
      sort($imported_classes);
      foreach ($imported_classes as $fully_qualified_class_name) {
        $fully_qualified_class_name = ltrim($fully_qualified_class_name, '\\');
        $imports[] = "use $fully_qualified_class_name;";
      }

      $imports[] = '';
    }

    return $imports;
  }

  /**
   * Procudes the docblock for the class.
   */
  protected function class_doc_block() {
    return $this->docBlock("TODO: class docs.");
  }

  /**
   * Produces the class declaration.
   */
  function class_declaration() {
    return [
      "class $this->plain_class_name {",
    ];
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

  /**
   * Creates code lines for the docblock and declaration line of a method.
   *
   * TODO: refactor with PHPFunction class or PHPFormattingTrait.
   *
   * @param $name
   *  The method name (without the ()).
   * @param $parameters
   *  (optional) An array of data about the parameters. The key is immaterial;
   *  each value is an array with these properties:
   *  - 'name': The name of the parameter, without the initial $.
   *  - 'typehint': The typehint of the parameter. If this is a class or
   *    interface, use the fully-qualified form: this will produce import
   *    statements for the file automatically.
   *  - 'description': (optional) The description of the parameter. This may be
   *    omitted if 'inheritdoc' is passed into the options.
   *    (Single line only supported for now.)
   * @param $options
   *  An array of options. May contain:
   *  - 'docblock_first_line' (optional): The text for the first line of the
   *    docblock. (Required unless 'inheritdoc' is set.)
   *  - 'inheritdoc': If TRUE, indicates that the docblock is an @inheritdoc
   *    tag.
   *  - 'prefixes': (optional) An array of prefixes such as 'static', 'public'.
   *
   * @return
   *  An array of code lines.
   */
  protected function buildMethodHeader($name, $parameters = [], $options = []) {
    $options += [
      'inheritdoc' => FALSE,
      'prefixes' => [],
    ];

    $code = [];

    $docblock_content_lines = [];

    if ($options['inheritdoc']) {
      $docblock_content_lines[] = '{@inheritdoc}';
    }
    else {
      $docblock_content_lines[] = $options['docblock_first_line'];
      if (!empty($parameters)) {
        $docblock_content_lines[] = '';
        foreach ($parameters as $parameter_info) {
          $docblock_content_lines[] = "@param " . $parameter_info['typehint'] . ' $' . $parameter_info['name'];
          $docblock_content_lines[] = '  ' . $parameter_info['description'];
        }
        // TODO: @return line.
      }
    }

    $code = array_merge($code, $this->docBlock($docblock_content_lines));

    $declaration_line = '';
    foreach ($options['prefixes'] as $prefix) {
      $declaration_line .= $prefix . ' ';
    }
    $declaration_line .= 'function ' . $name . '(';
    $declaration_line_params = [];
    foreach ($parameters as $parameter_info) {
      if (isset($parameter_info['typehint']) && in_array($parameter_info['typehint'], ['string', 'bool', 'mixed', 'int'])) {
        // Don't type hint scalar types.
        $declaration_line_params[] = '$' . $parameter_info['name'];
      }
      elseif (isset($parameter_info['typehint'])) {
        $declaration_line_params[] = $parameter_info['typehint'] . ' $' . $parameter_info['name'];
      }
      else {
        $declaration_line_params[] = '$' . $parameter_info['name'];
      }
    }
    $declaration_line .= implode(', ', $declaration_line_params);
    $declaration_line .= ') {';

    $code[] = $declaration_line;

    return $code;
  }

}
