<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\DocBlock;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Data\DataItem;

/**
 * Generator for PHP class files.
 */
class PHPClassFile extends PHPFile {

  /**
   * The collected traits.
   *
   * @var array
   */
  protected $traits;

  /**
   * The collected constants.
   *
   * @var array
   */
  protected $constants;

  /**
   * The collected properties.
   *
   * @var array
   */
  protected $properties;

  /**
   * Ordering of generated class methods.
   *
   * This allows easy overriding by child classes.
   *
   * @var array
   */
  protected $functionOrdering = [
    'static',
    '__construct',
    'public',
    'protected',
    'OTHER',
  ];

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

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
      'filename' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setExpressionDefault("get('..:path') ~ '/' ~ get('..:plain_class_name') ~ '.php'"),
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
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return $this->component_data->relative_class_name->value;
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
   * Returns file header code.
   */
  function code_header() {
    // Class files have no file docblock. Return an empty array.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  function phpCodeBody() {
    // Get the class code from the class docblock onwards first, so it can be
    // then processed for qualified class names.
    $class_code = $this->phpClassCode();

    // Replace any fully-qualified classes with short class names, and keep a
    // list of the replacements to make import statements with.
    $imported_classes = [];
    $this->extractFullyQualifiedClasses($class_code, $imported_classes, $this->component_data['namespace']);

    $return = array_merge(
      $this->code_namespace(),
      $this->imports($imported_classes),
      $class_code,
      [
        '',
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
   * Gets the complete class code, from docblock to closing brace.
   *
   * @return array
   *   An array of code pieces.
   */
  protected function phpClassCode(): array {
    $class_doc_block = $this->getClassDocBlock();
    $class_attributes = $this->getClassAttributes();

    $class_code = array_merge(
      $class_doc_block->render(),
      $class_attributes?->render() ?? [],
      $this->class_declaration(),
      $this->classCodeBody(),
      [
        '}',
      ],
    );

    return $class_code;
  }

  /**
   * Gets the !!! lines to format as the docblock.
   *
   * @return string[] !!
   *   An array of lines.
   */
  protected function getClassDocBlock(): DocBlock {
    $docblock = DocBlock::class();

    if ($this->component_data->class_docblock_lines->value) {
      // This is a mapping not a multiple string for FKW reasons.
      foreach ($this->component_data->class_docblock_lines->value as $line) {
        $docblock[] = $line;
      }
    }
    elseif ($this->component_data->docblock_first_line->value) {
      $docblock[] = $this->component_data['docblock_first_line'];
    }
    else {
      // Complain here, as every class should have something for its first
      // docblock line.
      assert(FALSE, "Missing first docblock line.");
    }

    return $docblock;
  }

  /**
   * Produces the class attributes.
   *
   * @return \DrupalCodeBuilder\Generator\Render\PhpAttributes|null
   *   An attribute object if this class has attributes.
   */
  protected function getClassAttributes(): ?PhpAttributes {
    return NULL;
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
      // Most functions are made with contained components, but some remain
      // created with section blocks, in particular, those for dependency
      // injection.
      'functions',
    ];
    foreach ($section_types as $section_type) {
      $section_blocks = $this->getSectionBlocks($section_type);
      $code_body = array_merge($code_body, $this->mergeSectionCode($section_blocks));
    }

    // Order functions by static, constructor, public, others.
    // This is done here rather than with a sort callback implemented in the
    // component class as it's the PHP class which has an opinion on how the
    // methods should be arranged.
    $grouped_function_components = array_fill_keys($this->functionOrdering, []);
    foreach ($this->containedComponents['function'] as $key => $child_item) {
      $prefixes = $child_item->component_data->prefixes->export();
      if (in_array('static', $prefixes)) {
        $grouped_function_components['static'][$key] = $child_item;
        continue;
      }

      if ($child_item->component_data->function_name->value == '__construct') {
        $grouped_function_components['__construct'][$key] = $child_item;
        continue;
      }

      if (in_array('public', $prefixes)) {
        $grouped_function_components['public'][$key] = $child_item;
        continue;
      }

      if (in_array('protected', $prefixes)) {
        $grouped_function_components['protected'][$key] = $child_item;
        continue;
      }

      $grouped_function_components['OTHER'][$key] = $child_item;
    }

    foreach ($grouped_function_components as $group_functions) {
      foreach ($group_functions as $key => $child_item) {
        $content = $child_item->getContents();
        $code_body = array_merge($code_body, $content);

        // Blank line after each function.
        $code_body[] = '';
      }
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

    foreach ($this->containedComponents['constant'] as $key => $child_item) {
      $this->constants[] = $child_item->getContents();
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

    $docblock = Docblock::property();

    $docblock[] = $options['docblock_first_line'];
    foreach ($options['docblock_lines'] ?? [] as $docblock_line) {
      $docblock[] = $docblock_line;
    }

    $docblock->var($type);

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
      $docblock->render(),
      $declaration_lines
    );

    return $property_code;
  }

  /**
   * Creates a function component from analysis data for class methods.
   *
   * Helper for requiredComponents().
   *
   * @param $methods_data
   *   An array of method data as returned by reporting.
   *
   * @return array
   *   An array for a PHPFunction component for requiredComponents().
   */
  protected function createFunctionComponentFromMethodData($method_data): array {
    // Trim the semicolon from the end of the interface method.
    $method_declaration = substr($method_data['declaration'], 0, -1);

    // Add a comment with the method's first line of docblock, so the user
    // has something more informative than '{@inheritdoc}' to go on!
    $comment = $method_data['description'] ?? 'Method has no documentation!';

    // Babysit documentation that is missing a final full stop, so PHP
    // Codesniffer doesn't complain in our own tests, and we output correctly
    // formatted code ourselves.
    // (This can happen either if the full stop is missing, or if the first
    // line overruns to two, in which case our analysis will have truncated
    // the sentence.)
    if (!str_ends_with($comment, '.')) {
      $comment .= '.';
    }

    $component = [
      'component_type' => 'PHPFunction',
      'containing_component' => '%requester',
      'function_name' => $method_data['name'],
      'declaration' => $method_declaration,
      'docblock_inherit' => TRUE,
      'body' => [
        '// ' . $comment,
      ],

    ];

    return $component;
  }

  /**
   * Helper to extract parts of contents.
   *
   * This is needed because InjectedService returns a nested array of different
   * items rather than a single array of items.
   *
   * TODO: Rethink this; it was a quick hack in the conversion from doing this
   * in the now removed buildComponentContents().
   *
   * @param string $type
   *
   * @return array
   */
  protected function getContentsElement(string $type): array {
    $subcontents = [];
    foreach ($this->containedComponents['injected_service'] as $key => $child_item) {
      $child_contents = $child_item->getContents();

      if (isset($child_contents[$type])) {
        $subcontents[$key] = $child_contents[$type];
      }
    }

    return $subcontents;
  }

}
