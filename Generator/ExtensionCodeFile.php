<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\File\CodeFile;
use DrupalCodeBuilder\File\DrupalExtension;

/**
 * Generator class for procedural code files.
 */
class ExtensionCodeFile extends PHPFile {

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
   * {@inheritdoc}
   */
  public function getFileInfo(): CodeFile {
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

    return new CodeFile(
      $this->fileContents(),
      build_list_tags: ['code', $file_key_tag],
      merged: $this->merged,
    );
  }

  /**
   * Return the main body of the file code.
   *
   * @return
   *  An array of code lines. Keys are immaterial but should avoid clashing.
   */
  function phpCodeBody() {
    // Keep each function separate for now, so they can be ordered.
    $function_code = [];

    // Array of the names of generated functions.
    $generated_function_names = [];
    $generated_function_names_without_suffixes = [];

    foreach ($this->containedComponents['function'] as $key => $child_item) {
      // Subtle (and brittle) point: call getContents first so that any value
      // replacement can take place. E.g. HookUpdateN setting the schema number.
      $function_lines = $child_item->getContents();

      $function_name = $child_item->component_data->function_name->value;
      $function_name = str_replace('%module', $this->component_data['root_component_name'], $function_name);
      $function_name = str_replace('%extension', $this->component_data['root_component_name'], $function_name);
      $generated_function_names[$function_name] = TRUE;
      if (preg_match('@\d+$@', $function_name)) {
        $generated_function_names_without_suffixes[$function_name] = preg_replace('@\d+$@', '', $function_name);
      }

      // Blank line after the function.
      $function_lines[] = '';

      $function_code[$function_name] = $function_lines;
    }

    // Merge any existing functions.
    $existing_function_order = [];
    $existing_function_names_without_suffixes = [];
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

        // Allow for crappy code with missing docblock.
        if ($comments) {
          $docblock = end($comments);

          $first_function->setAttribute('comments', [$docblock]);
        }

        // Only act on the first function.
        break;
      }

      // Add functions from the existing file, unless we are generating them
      // too, in which case we assume that our version is better.
      // Keep track of their order.
      $index = 0;
      foreach ($existing_function_nodes as $function_node) {
        $existing_function_name = (string) $function_node->name;

        $existing_function_order[$existing_function_name] = $index;
        $index++;

        if (preg_match('@\d+$@', $existing_function_name)) {
          $existing_function_names_without_suffixes[$existing_function_name] = preg_replace('@\d+$@', '', $existing_function_name);
        }

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

        $function_code[$existing_function_name] = $this->extension->getFileLines($this->getFilename(), $first_line, $end_line);
      }
    }

    // Assemble the code.
    $code_body = [];
    // Generated functions go first, unless they match existing functions to
    // within a numeric suffix.
    foreach (array_keys($generated_function_names) as $function_name) {
      // This matches an existing function to within a suffix: leave it for now
      // because we insert it among existing functions.
      if (
        isset($generated_function_names_without_suffixes[$function_name]) &&
        in_array($generated_function_names_without_suffixes[$function_name], $existing_function_names_without_suffixes)
      ) {
        continue;
      }

      $code_body[$function_name] = $function_code[$function_name];
    }

    foreach (array_keys($existing_function_order) as $function_name) {
      // Add the function.
      $code_body[$function_name] = $function_code[$function_name];

      // Now see if a generated function that we held over needs to go next.
      if (isset($existing_function_names_without_suffixes[$function_name])) {
        // Make a function name with the suffix incremented by 1 and see if it
        // exists in the generated functions.
        $numeric_suffix = substr($function_name, strlen($existing_function_names_without_suffixes[$function_name]));

        $potential_generated_function_name = $existing_function_names_without_suffixes[$function_name] . ($numeric_suffix + 1);

        if (isset($generated_function_names[$potential_generated_function_name])) {
          $code_body[$potential_generated_function_name] = $function_code[$potential_generated_function_name];
        }
      }
    }

    if (empty($code_body)) {
      // If there are no functions, then this is a .module file that's been
      // requested so the module is correctly formed. It is customary to add a
      // comment to the file for DX.
      $code_body['empty'] = "// Drupal needs this blank file.";
      $code_body[] = '';
    }
    else {
      // Merge the arrays of code lines for each function.
      $code_body = array_merge(...array_values($code_body));
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
      'module',
      'profile' => 'Contains hook implementations for the %readable %base.',
      'install' => 'Contains install and update hooks for the %readable %base.',
      default => parent::fileDocblockSummary(),
    };
  }

  /**
   * {@inheritdoc}
   */
  function codeFooter() {
    $footer = \DrupalCodeBuilder\Factory::getEnvironment()->getSetting('footer', NULL);
    return $footer;
  }

}
