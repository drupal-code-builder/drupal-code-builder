<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\File\DrupalExtension;

/**
 * Generator class for module code files.
 */
class ModuleCodeFile extends PHPFile {

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
    $this->exists = $extension->hasFile($this->component_data['filename']);

    if (!$this->exists) {
      return;
    }

    $ast = $extension->getFileAST($this->component_data['filename']);
    $ast = $extension->getASTFunctions($ast);

    // The first function in the AST will have an additional comment block for
    // the @file tag. Remove this, since we will generate it ourselves.
    if (isset($ast[0])) {
      $first_function = $ast[0];
      $comments = $first_function->getAttribute('comments');
      $docblock = end($comments);

      $first_function->setAttribute('comments', [$docblock]);
    }

    // No idea of format here! Probably unique for each generator!
    // For info files, the only thing which is mergeable
    $this->existing = $ast;
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

    // Function data has been set by buildComponentContents().
    foreach ($this->functions as $function_lines) {
      $code_body = array_merge($code_body, $function_lines);
      // Blank line after the function.
      $code_body[] = '';
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
    if (end($filename_pieces) == 'module') {
      return "Contains hook implementations for the %readable module.";
    }
    else {
      // TODO: handle other .inc files that contain hooks?
      return parent::fileDocblockSummary();
    }
  }

  /**
   * Return a file footer.
   */
  function code_footer() {
    $footer = \DrupalCodeBuilder\Factory::getEnvironment()->getSetting('footer', '');
    return $footer;
  }

}
