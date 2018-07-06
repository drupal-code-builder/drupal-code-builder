<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * Generator for PHP class files.
 */
class PHPClassFile extends PHPFile {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'relative_class_name' => [
        // E.g. ['Form', 'MyFormClass']
        'label' => 'The qualifed classname pieces, relative to the module namespace.',
        'format' => 'array',
        'internal' => TRUE,
      ],
      // E.g. ['Drupal', 'my_module', 'Form', 'MyFormClass']
      'qualified_class_name_pieces' => [
        'computed' => TRUE,
        'format' => 'array',
        'default' => function($component_data) {
          $class_name_pieces = array_merge([
            'Drupal',
            '%module',
          ], $component_data['relative_class_name']);

          return $class_name_pieces;
        },
      ],
      // E.g. 'Drupal\my_module\Form\MyFormClass'
      'qualified_class_name' => [
        'computed' => TRUE,
        'format' => 'string',
        'default' => function($component_data) {
          $class_name_pieces = array_merge([
            'Drupal',
            '%module',
          ], $component_data['relative_class_name']);

          return self::makeQualifiedClassName($class_name_pieces);
        },
      ],
      // This comes after the relative classname, since internally we usually
      // want to specify that rather than this.
      // For UIs though, it'll be the other way round: the user is asked the
      // short class name, and other things should be derived from it. To do
      // this, subclasses should add a property ahead of all these, and then
      // derive relative_class_name.
      'plain_class_name' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          return end($component_data['qualified_class_name_pieces']);
        },
      ],
      // The namespace, without the inital '\'.
      'namespace' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          $qualified_class_name_pieces = $component_data['qualified_class_name_pieces'];
          array_pop($qualified_class_name_pieces);

          return implode('\\', $qualified_class_name_pieces);
        },
      ],
      'path' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          // Lop off the initial Drupal\module and the final class name to
          // build the path.
          $path_pieces = array_slice($component_data['qualified_class_name_pieces'], 2, -1);
          // Add the initial src to the front.
          array_unshift($path_pieces, 'src');

          return implode('/', $path_pieces);
        },
      ],
      // Deprecated: use class_docblock_lines instead.
      'docblock_first_line' => [
        'format' => 'string',
        'internal' => TRUE,
        'default' => function($component_data) {
          return 'TODO: class docs.';
        },
      ],
      // Lines for the class docblock.
      // If there is more than one line, a blank link is inserted automatically
      // after the first one.
      'class_docblock_lines' => [
        'format' => 'array',
        'internal' => TRUE,
        // No default, as most generators don't use this yet.
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
      'traits' => [
        'label' => 'Traits',
        'description' => 'List of traits this class uses, as fully-qualified names with initial \.',
        'format' => 'array',
        'internal' => TRUE,
        'default' => [],
      ],
    ];
  }

  /**
   * Build the code files.
   *
   * Subclasses should override this to add their file data to the list.
   */
  public function getFileInfo() {
    return array(
      'path' => $this->component_data['path'],
      'filename' => $this->component_data['plain_class_name'] . '.php',
      'body' => $this->fileContents(),
      'build_list_tags' => ['code'],
    );
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
    $class_doc_block = $this->getClassDocBlockLines();
    $class_doc_block = $this->docBlock($class_doc_block);

    $class_code = array_merge(
      $class_doc_block,
      $this->class_declaration(),
      $this->classCodeBody()
    );

    // Replace any fully-qualified classes with short class names, and keep a
    // list of the replacements to make import statements with.
    $imported_classes = [];
    $this->extractFullyQualifiedClasses($class_code, $imported_classes, $this->component_data['namespace']);

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

    $code[] = 'namespace ' . $this->component_data['namespace'] . ';';
    $code[] = '';

    return $code;
  }

  /**
   * Gets the bare lines to format as the docblock.
   *
   * @return string[]
   *   An array of lines.
   */
  protected function getClassDocBlockLines() {
    $lines = [];

    if (!empty($this->component_data['class_docblock_lines'])) {
      $lines = $this->component_data['class_docblock_lines'];

      if (count($lines) > 1) {
        // If there is more than one line, splice in a blank line after the
        // first one.
        array_splice($lines, 1, 0, '');
      }
    }
    elseif (!empty($this->component_data['docblock_first_line'])) {
      $lines[] = $this->component_data['docblock_first_line'];
    }

    return $lines;
  }

  /**
   * Produces the class declaration.
   */
  function class_declaration() {
    $line = '';
    if ($this->component_data['abstract']) {
      $line .= 'abstract ';
    }
    $line .= "class {$this->component_data['plain_class_name']}";
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

    // Let the class collect its section blocks.
    $this->collectSectionBlocks();

    $section_types = [
      // These are the names of class properties.
      // TODO: change these to being roles in the child components.
      'traits',
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
   * Collect the section blocks for the class code.
   *
   * This should set section blocks as class properties whose names are the
   * section types defined in classCodeBody().
   */
  protected function collectSectionBlocks() {
    foreach ($this->component_data['traits'] as $trait) {
      $this->traits[] = [
        "use {$trait};",
      ];
    }
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
   * Create a property block for use by getSectionBlocks().
   *
   * @param string $property_name
   *   The property's name, without the initial '$'.
   * @param string $type
   *   The typehint. Classes and interfaces should be fully-qualified, with the
   *   initial '\'.
   * @param mixed $description
   *   A single-line description for the docblock, as a string, or an array of
   *   lines. If an array, the first item must be the single-line first docblock
   *   line, and the second item must be an empty string.
   * @param array $modifiers
   *   An array of the modifiers. Defaults to 'protected'.
   * @param $default
   *   The default value, as the actual value. May be any type.
   * @param $options
   *  An array of options. May contain:
   *  - 'docblock_first_line' The text for the first line of the docblock.
   *  - 'docblock_lines' (optional) An array of further docblock lines.
   *  - 'default': (optional) The default value, as the actual value. May be any
   *    type.
   *  - 'prefixes': (optional) An array of prefixes such as 'static', 'public'.
   *    Defaults to 'protected'.
   *  - 'break_array_value': (optional) If TRUE, the declaration parameters
   *    are each on a single line.
   *
   * @return
   *  An array suitable to be set for getSectionBlocks().
   */
  protected function createPropertyBlock($property_name, $type, $options) {
    $options += [
      'default' => NULL,
      'prefixes' => ['protected'],
      'break_array_value' => FALSE,
    ];

    $docblock_lines = [];

    $docblock_lines[] = $options['docblock_first_line'];

    if (!empty($options['docblock_lines'])) {
      $docblock_lines[] = '';
      $docblock_lines = array_merge($docblock_lines, $options['docblock_lines']);
    }

    $docblock_lines[] = '';
    $docblock_lines[] = '@var ' . $type;

    $declaration_lines = [];
    $declaration_first_line = '';

    if (!empty($options['prefixes'])) {
      $declaration_first_line .= implode(' ', $options['prefixes']) . ' ';
    }

    $declaration_first_line .= '$' . $property_name;

    // Check for the actual key, as the value we want to place could be NULL.
    if (array_key_exists('default', $options)) {
      $declaration_first_line .= ' = ';

      if (is_array($options['default'])) {
        if ($options['break_array_value']) {
          $declaration_first_line .= '[';
          $declaration_lines[] = $declaration_first_line;

          foreach ($options['default'] as $default_value_array_item) {
            // TODO: assuming these are all strings for now!
            $declaration_lines[] = '  ' . "'{$default_value_array_item}',";
          }

          $declaration_lines[] = '];';
        }
        else {
          $declaration_first_line .= '[' . implode(', ', array_map(function ($value) {
            return "'$value'";
          }, $options['default'])) . ']';

          $declaration_first_line .= ';';
          $declaration_lines[] = $declaration_first_line;
        }
      }
      elseif (is_bool($options['default'])) {
        $declaration_first_line .= strtoupper((string) $options['default']);

        $declaration_first_line .= ';';
        $declaration_lines[] = $declaration_first_line;
      }
      elseif (is_numeric($options['default'])) {
        $declaration_first_line .= $options['default'];

        $declaration_first_line .= ';';
        $declaration_lines[] = $declaration_first_line;
      }
      else {
        $declaration_first_line .= "'{$options['default']}'";

        $declaration_first_line .= ';';
        $declaration_lines[] = $declaration_first_line;
      }
    }


    $property_code = array_merge(
      $this->docBlock($docblock_lines),
      $declaration_lines
    );

    return $property_code;
  }

  /**
   * Create blocks in the 'function' section from data describing methods.
   *
   * @param $methods_data
   *   An array of method data as returned by reporting.
   */
  protected function createBlocksFromMethodData($methods_data) {
    foreach ($methods_data as $interface_method_name => $interface_method_data) {
      $function_code = [];
      $function_doc = $this->docBlock('{@inheritdoc}');
      $function_code = array_merge($function_code, $function_doc);

      // Trim the semicolon from the end of the interface method.
      $method_declaration = substr($interface_method_data['declaration'], 0, -1);

      $function_code[] = "$method_declaration {";
      // Add a comment with the method's first line of docblock, so the user
      // has something more informative than '{@inheritdoc}' to go on!

      // Babysit documentation that is missing a final full stop, so PHP
      // Codesniffer doesn't complain in our own tests, and we output correctly
      // formatted code ourselves.
      // (This can happen either if the full stop is missing, or if the first
      // line overruns to two, in which case our analysis will have truncated
      // the sentence.)
      if (substr($interface_method_data['description'], -1) != '.') {
        $interface_method_data['description'] .= '.';
      }

      $function_code[] = '  // ' . $interface_method_data['description'];
      $function_code[] = '}';

      // Add to the functions section array for the parent to merge.
      $this->functions[] = $function_code;
    }
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
   *  - 'typehint': (optional) The typehint of the parameter. If this is a class
   *    or interface, use the fully-qualified form: this will produce import
   *    statements for the file automatically.
   *  - 'description': (optional) The description of the parameter. This may be
   *    omitted if 'inheritdoc' is passed into the options.
   * @param $options
   *  An array of options. May contain:
   *  - 'docblock_first_line' (optional): The text for the first line of the
   *    docblock. (Required unless 'inheritdoc' is set.)
   *  - 'inheritdoc': If TRUE, indicates that the docblock is an @inheritdoc
   *    tag.
   *  - 'prefixes': (optional) An array of prefixes such as 'static', 'public'.
   *  - 'break_declaration': (optional) If TRUE, the declaration parameters
   *    are each on a single line.
   *
   * @return
   *  An array of code lines.
   */
  protected function buildMethodHeader($name, $parameters = [], $options = []) {
    $options += [
      'inheritdoc' => FALSE,
      'prefixes' => [],
      'break_declaration' => FALSE,
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
          $docblock_content_lines[] = "@param "
            . (
              empty($parameter_info['typehint'])
              ? ''
              : $parameter_info['typehint'] . ' '
            )
            . '$' . $parameter_info['name'];

          // Generate a parameter description from the name if none was given.
          if (empty($parameter_info['description'])) {
            // TODO: add a 'lower' case to case converter.
            $parameter_info['description'] = CaseString::snake('The_' . $parameter_info['name'])->sentence() . '.';
          }

          // Wrap the description to 80 characters minus the indentation.
          $indent_count =
            2 // Class code indent.
            + 2 // Space and the doc comment asterisk.
            + 3; // Indentation for the parameter description.
          $wrapped_description = wordwrap($parameter_info['description'], 80 - $indent_count);
          $wrapped_description_lines = explode("\n", $wrapped_description);

          foreach ($wrapped_description_lines as $line) {
            $docblock_content_lines[] = '  ' . $line;
          }
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
      if (!empty($parameter_info['typehint']) && in_array($parameter_info['typehint'], ['string', 'bool', 'mixed', 'int'])) {
        // Don't type hint scalar types.
        $declaration_line_params[] = '$' . $parameter_info['name'];
      }
      elseif (!empty($parameter_info['typehint'])) {
        $declaration_line_params[] = $parameter_info['typehint'] . ' $' . $parameter_info['name'];
      }
      else {
        $declaration_line_params[] = '$' . $parameter_info['name'];
      }
    }

    if ($options['break_declaration']) {
      // The function declaration up to the opening '(' is one line.
      $code[] = $declaration_line;

      $last_index = count($declaration_line_params) - 1;
      foreach ($declaration_line_params as $index => $param) {
        $code[] = '  ' . $param . ( $index == $last_index ? '' : ',' );
      }

      $code[] = ') {';
    }
    else {
      $declaration_line .= implode(', ', $declaration_line_params);
      $declaration_line .= ') {';

      $code[] = $declaration_line;
    }

    return $code;
  }

}
