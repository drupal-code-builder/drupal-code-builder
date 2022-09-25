<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\File\DrupalExtension;

/**
 * Generator class for module code files.
 */
class ModuleCodeFile extends PHPFile {

  /**
   * Whether this file is merged with existing code.
   *
   * @todo Move this up the class hierarchy to PHPFIle when it's used there and
   * in all child classes.
   *
   * @var bool
   */
  protected $merged = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return $this->component_data['filename'];
  }

  /**
   * {@inheritdoc}
   */
  public function detectExistence(DrupalExtension $extension) {
    $this->exists = $extension->hasFile($this->getFilename());
    $this->extension = $extension;
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    // Create a build list tag from the filename.
    $filename_pieces = explode('.', $this->component_data['filename']);
    if ($filename_pieces[0] == '%module') {
      // Take off the module name from the front.
      array_shift($filename_pieces);
    }
    if (in_array(end($filename_pieces), ['php', 'inc'])) {
      // Take off a file extenstion.
      array_pop($filename_pieces);
    }
    // Implode whatever's left.
    $file_key_tag = implode('.', $filename_pieces);

    return [
      'path' => '', // Means base folder.
      'filename' => $this->component_data['filename'],
      'body' => $this->fileContents(),
      'build_list_tags' => ['code', $file_key_tag],
      'merged' => $this->merged,
    ];
  }

  /**
   * Return the main body of the file code.
   *
   * @return
   *  An array of code lines. Keys are immaterial but should avoid clashing.
   */
  function code_body() {
    $code_body = [];

    // Array of the names of generated functions.
    $generated_function_names = [];

    foreach ($this->containedComponents['function'] as $key => $child_item) {
      $function_name = $child_item->component_data->function_name->value;
      $function_name = str_replace('%module', $this->component_data['root_component_name'], $function_name);
      $generated_function_names[$function_name] = TRUE;

      $function_lines = $child_item->getContents();
      $code_body = array_merge($code_body, $function_lines);
      // Blank line after the function.
      $code_body[] = '';
    }

    // Merge any existing functions.
    if ($this->exists) {
      $ast = $this->extension->getFileAST($this->getFilename());

      $existing_function_nodes = $this->extension->getASTFunctions($ast);

      if ($existing_function_nodes) {
        $this->merged = TRUE;
      }

      // The first function in the AST might have an additional comment block for
      // the @file tag. Remove this, since we will generate it ourselves.
      foreach ($ast as $ast_node) {
        if ($ast_node->getType() != 'Stmt_Function') {
          continue;
        }

        $first_function = $ast_node;
        $comments = $first_function->getAttribute('comments');
        $docblock = end($comments);

        $first_function->setAttribute('comments', [$docblock]);

        // Only act on the first function.
        break;
      }

      // Add functions from the existing file, unless we are generating them
      // too, in which case we assume that our version is better.
      foreach ($existing_function_nodes as $function_node) {
        $existing_function_name = (string) $function_node->name;

        // Skip if the function has already been generated: a generated function
        // overwrites an existing one.
        if (isset($generated_function_names[$existing_function_name])) {
          continue;
        }

        if ($comments = $function_node->getAttribute('comments')) {
          $first_line = $comments[0]->getStartLine();
        }
        else {
          $first_line = $function_node->getStartLine();
        }

        $end_line = $function_node->getEndLine() + 1;

        $code_body[$existing_function_name] = implode("\n", $this->extension->getFileLines($this->getFilename(), $first_line, $end_line));
      }
    }

    // If there are no functions, then this is a .module file that's been
    // requested so the module is correctly formed. It is customary to add a
    // comment to the file for DX.
    if (empty($code_body)) {
      $code_body['empty'] = "// Drupal needs this blank file.";
      $code_body[] = '';
    }

    // Replace any fully-qualified classes with short class names, and keep a
    // list of the replacements to make import statements with.
    $imported_classes = [];
    $this->extractFullyQualifiedClasses($code_body, $imported_classes);

    // Merge any existing import statements.
    if ($this->exists) {
      $existing_import_nodes = $this->extension->getASTImports($ast);

      foreach ($existing_import_nodes as $import_node) {
        $existing_import = $import_node->uses[0]->name->toString();
        $imported_classes[] = $existing_import;
      }
    }

    $return = array_merge(
      $this->imports($imported_classes),
      $code_body
    );
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  function fileDocblockSummary() {
    $filename_pieces = explode('.', $this->component_data['filename']);

    return match (end($filename_pieces)) {
      'module' => 'Contains hook implementations for the %readable module.',
      'install' => 'Contains install and update hooks for the %readable module.',
      default => parent::fileDocblockSummary(),
    };
  }

  /**
   * {@inheritdoc}
   */
  function code_footer() {
    $footer = \DrupalCodeBuilder\Factory::getEnvironment()->getSetting('footer', NULL);
    return $footer;
  }

}
