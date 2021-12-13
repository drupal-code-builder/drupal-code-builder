<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Data\DataItem;

/**
 * Generator for PHP class files.
 */
class PHPClassFile extends PHPFile {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      // The class name properties all form an interdependent set.
      // Typically, UIs will allow users to specify either:
      //  - the plain class name, such as for entity classes or plugins, where
      //    the namespace is fixed.
      // - the relative class name, such as for services where the user might
      //   with to put the service within a separate namespace.
      // Internally, we typically specify the relative class name, as a mapping
      // value rather than a string for easier manipulation.
      // The chain of dependencies of defaults goes either:
      /*  A. - plain_class_name -- this means it's top-level in the module namespace, OR
              has a fixed relative namespace that comes from the generator
            - relative_namespace - fixed. literal default may be overridden.
            -> relative_class_name_pieces = array_append(relative_namespace_pieces, plain_class_name)
            -> relative_class_name - implode relative_class_name_pieces
            -> qualified_class_name_pieces - why do we need this again???
               we only need relative_namespace_pieces to derive the path!
            -> qualified_class_name
         B. - relative_class_name -- such as service, controller, etc.
            -> plain_class_name -- substring relative_class_name -- CAN'T go via relative_class_name_pieces -- circularity!
            -> relative_class_name_pieces = relative_namespace ~ plain_class_name)
            -> relative_namespace - from relative_class_name
            -> qualified_class_name_pieces
            -> qualified_class_name
      */
      // One and *ONLY ONE* of relative_class_name and plain_class_name must be
      // exposed; the other must be set internal.
      'relative_class_name' => PropertyDefinition::create('string')
        ->setLabel('The qualifed class name, relative to the module namespace, e.g. "Controller\MyController"')
        ->setDefault(DefaultDefinition::create()
          ->setCallable(function (DataItem $component_data) {
            $class_data = $component_data->getParent();
            $relative_namespace = $class_data->relative_namespace->value;
            $plain_classname = $class_data->plain_class_name->value;

            if ($relative_namespace) {
              $relative_namespace .= '\\';
            }
            return $relative_namespace . $plain_classname;
        })
        ->setDependencies('..:relative_namespace', '..:plain_class_name')
      ),
      // TODO: processing for case.
      'plain_class_name' => PropertyDefinition::create('string')
        ->setLabel('The plain class name, e.g. "MyClass"')
        ->setDefault(DefaultDefinition::create()
          ->setExpression("plainClassNameFromQualified(parent.relative_class_name.get())")
          ->setDependencies('..:relative_class_name')
        )
        ->setValidators('class_name'),
      // Child classes that expose 'plain_class_name' must set a non-lazy
      // literal default for this.
      // Should not start or end with a backslash.
      'relative_namespace' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setLiteral('')
      ),
      // E.g. ['Form', 'MyFormClass']
      'relative_class_name_pieces' => PropertyDefinition::create('mapping')
        ->setLabel('The qualifed classname pieces, relative to the module namespace')
        ->setInternal(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setCallable(function (DataItem $component_data) {
            return explode("\\", $component_data->getParent()->relative_class_name->value);
          })
          ->setDependencies('..:relative_class_name')
      ),
      // E.g. ['Drupal', 'my_module', 'Form', 'MyFormClass']
      'qualified_class_name_pieces' => PropertyDefinition::create('mapping')
        ->setInternal(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setCallable(function (DataItem $component_data) {
            $default = array_merge(
              [
                'Drupal',
                $component_data->getParent()->root_component_name->value,
              ],
              $component_data->getParent()->relative_class_name_pieces->value,
            );
            return $default;
          })
          ->setDependencies('..:relative_class_name_pieces')
      ),
      'namespace' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setRequired(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setExpression("namespaceFromPieces(parent.qualified_class_name_pieces.get())")
          ->setDependencies('..:qualified_class_name_pieces')
      ),
      // E.g. 'Drupal\my_module\Form\MyFormClass'
      'qualified_class_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setRequired(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setExpression("implode('\\\\', parent.qualified_class_name_pieces.get())")
          ->setDependencies('..:qualified_class_name_pieces')
      ),
      'path' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("pathFromQualifiedClassNamePieces(parent.qualified_class_name_pieces.get())")
            ->setDependencies('..:qualified_class_name_pieces')
        ),
      // Deprecated: use class_docblock_lines instead.
      'docblock_first_line' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault('TODO: class docs.'),
      // Lines for the class docblock.
      // If there is more than one line, a blank link is inserted automatically
      // after the first one.
      'class_docblock_lines' => PropertyDefinition::create('mapping')
        ->setInternal(TRUE),
        // No default, as most generators don't use this yet.
      'abstract' => PropertyDefinition::create('boolean')
        ->setLabel('Abstract')
        ->setInternal(TRUE)
        ->setLiteralDefault(FALSE),
      // Inconsistent with other properties for this to be a string, but we tend
      // to have parents be a qualified class name.
      'parent_class_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setRequired(TRUE),
      // Cheat and use mapping for now, as multi-valued properties don't do
      // defaults. But should -- TODO.
      // List of interfaces this class implements, as fully-qualified names
      // with initial '\'.
      'interfaces' => PropertyDefinition::create('mapping')
        ->setInternal(TRUE),
      'traits' => PropertyDefinition::create('string')
        ->setLabel('Traits')
        ->setDescription('List of traits this class uses, as fully-qualified names with initial \.')
        ->setMultiple(TRUE)
        ->setInternal(TRUE),
    ]);

    return $definition;
  }

  /**
   * Build the code files.
   *
   * Subclasses should override this to add their file data to the list.
   */
  public function getFileInfo() {
    return [
      'path' => $this->component_data['path'],
      'filename' => $this->component_data['plain_class_name'] . '.php',
      'body' => $this->fileContents(),
      'build_list_tags' => ['code'],
    ];
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
      [
        $this->code_footer(),
      ]
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
    $code = [];

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

    if ($this->component_data->class_docblock_lines->value) {
      $lines = $this->component_data['class_docblock_lines'];

      if (count($lines) > 1) {
        // If there is more than one line, splice in a blank line after the
        // first one.
        array_splice($lines, 1, 0, '');
      }
    }
    elseif ($this->component_data->docblock_first_line->value) {
      $lines[] = $this->component_data['docblock_first_line'];
    }
    else {
      // Complain here, as every class should have something for its first
      // docblock line.
      assert(FALSE, "Missing first docblock line.");
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
      // Have to use export() instead of values() because of the hack with this
      // being mapping data, not multi-valued string.
      $line .= " implements " . implode(', ', $this->component_data->interfaces->export());
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
    $code_body = [];

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
    foreach ($this->component_data->traits->export() as $trait) {
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

          // TODO: we assume that numeric keys should not be output, but it's
          // possible for numeric keys to be out of order or interspersed and
          // therefore need to be output.

          foreach ($options['default'] as $default_value_array_item_key => $default_value_array_item) {
            // TODO: assuming these are all strings for now!
            if (is_numeric($default_value_array_item_key)) {
              $declaration_lines[] = '  ' . "'{$default_value_array_item}',";
            }
            else {
              $declaration_lines[] = '  ' . "'{$default_value_array_item_key}' => '{$default_value_array_item}',";
            }
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
   * @param string $return_type
   *  The return type.
   *
   * @return
   *  An array of code lines.
   */
  protected function buildMethodHeader($name, $parameters = [], $options = [], string $return_type = NULL) {
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

    if ($return_type) {
      $closing = "): $return_type {";
    }
    else {
      $closing = ') {';
    }

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

      $code[] = $closing;
    }
    else {
      $declaration_line .= implode(', ', $declaration_line_params);
      $declaration_line .= $closing;

      $code[] = $declaration_line;
    }

    return $code;
  }

}
