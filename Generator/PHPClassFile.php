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

    $this->setClassNames($component_data['relative_class_name']);
  }

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return [
      'relative_class_name' => [
        'label' => 'The qualifed classname pieces, relative to the module namespace.',
        'format' => 'array',
        'internal' => TRUE,
      ],
      'docblock_first_line' => [
        'format' => 'string',
        // May be set by requesters, but not by UIs.
        'computed' => TRUE,
        'default' => function($component_data) {
          return 'TODO: class docs.';
        },
      ],
      'abstract' => [
        'label' => 'Abstract',
        'format' => 'boolean',
        'internal' => TRUE,
        'default' => FALSE,
      ],
      'parent_class_name' => [
        'label' => 'The parent class name',
        // Inconsistent with other properties, but we tend to have parents be
        // class names from existing code.
        'format' => 'string',
        'internal' => TRUE,
        'default' => '',
      ],
      'interfaces' => [
        'label' => 'Interfaces',
        'description' => 'List of interfaces this class implements, as fully-qualified names with initial \.',
        'format' => 'array',
        'internal' => TRUE,
        'default' => [],
      ],
    ];
  }

  /**
   * Set properties relating to class name.
   *
   * @param array $relative_class_name_pieces
   *  TODO The fully-qualified class name, e.g. 'Drupal\\foo\\Bar\\Classname'.
   */
  protected function setClassNames($relative_class_name_pieces) {
    $class_name_pieces = array_merge([
      'Drupal',
      '%module',
    ], $relative_class_name_pieces);

    $this->qualified_class_name = self::makeQualifiedClassName($class_name_pieces);

    $this->plain_class_name = array_pop($class_name_pieces);
    $this->namespace  = implode('\\', $class_name_pieces);
    $path_pieces = array_slice($class_name_pieces, 2);
    array_unshift($path_pieces, 'src');
    $this->path = implode('/', $path_pieces);
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
      'body' => $this->fileContents(),
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
  protected function fileContents() {
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
   * Returns file header code.
   */
  function code_header() {
    // Class files have no file docblock. Return an empty array.
    return [];
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
      $this->classCodeBody()
    );

    // Replace any fully-qualified classes with short class names, and keep a
    // list of the replacements to make import statements with.
    $imported_classes = [];
    $this->extractFullyQualifiedClasses($class_code, $imported_classes);

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
   * Procudes the docblock for the class.
   */
  protected function class_doc_block() {
    return $this->docBlock($this->component_data['docblock_first_line']);
  }

  /**
   * Produces the class declaration.
   */
  function class_declaration() {
    $line = '';
    if ($this->component_data['abstract']) {
      $line .= 'abstract ';
    }
    $line .= "class $this->plain_class_name";
    if ($this->component_data['parent_class_name']) {
      $line .= " extends {$this->component_data['parent_class_name']}";
    }
    if ($this->component_data['interfaces']) {
      foreach ($this->component_data['interfaces'] as $interface) {
        $line .= " implements $interface";
      }
    }
    $line .= ' {';

    return [
      $line,
    ];
  }

  /**
   * Return the body of the class's code.
   */
  protected function classCodeBody() {
    $code_body = array();

    // Class body always has at least one blank line.
    $code_body[] = '';


    $section_types = [
      // These are the names of class properties.
      // TODO: change these to being roles in the child components.
      'constants',
      'properties',
      'constructor',
      'functions',
    ];
    foreach ($section_types as $section_type) {
      $section_blocks = $this->getSectionBlocks($section_type);
      $code_body = array_merge($code_body, $this->mergeSectionCode($section_blocks));
    }

    // Indent all the class code.
    // TODO: is there a nice way of doing indents?
    $code_body = array_map(function ($line) {
      return empty($line) ? $line : '  ' . $line;
    }, $code_body);

    return $code_body;
  }

  /**
   * Returns the section blocks for the given section.
   *
   * Helper for classCodeBody(). We consider that class code is made up of sections such as
   * properties, constructor, methods. Each section has multiple blocks, i.e. the multiple
   * properties or methods (the constructor is a special case).
   *
   * For each type of section, we assemble an array of the blocks, where each block is an
   * array of code lines.
   *
   * Merging these items, and merging the different sections takes place in
   * mergeSectionCode(), which takes care of spacing between items and spacing between
   * different sections.
   *
   * @param string $section_type
   *   A section type is a string identifying the type of the section, e.g. 'functions'.
   *
   * @return array
   *   An array of blocks
   */
  protected function getSectionBlocks($section_type) {
    if ($section_type == 'constructor' && isset($this->constructor)) {
      // TODO: remove this special casing.
      $this->constructor = [$this->constructor];
    }

    $code_blocks = [];
    if (!empty($this->{$section_type})) {
      foreach ($this->{$section_type} as $component_name => $lines) {
        $code_blocks[] = $lines;
      }
    }

    return $code_blocks;
  }

  /**
   * Merge an array of blocks for a section.
   *
   * @param $section_blocks
   *   An array of section blocks. Each block is itself an array of code lines. There should
   *   be no
   *
   * @return
   *   An array of code lines.
   */
  protected function mergeSectionCode($section_blocks) {
    $code = [];
    foreach ($section_blocks as $block) {
      $code = array_merge($code, $block);

      // Blank line after each block.
      $code[] = '';
    }

    return $code;
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
