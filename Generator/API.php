<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\File\DrupalExtension;

/**
 * Component generator: api.php file for documention hooks and callbacks.
 *
 * This component should be requested once module code has been written. It
 * looks for calls to module_invoke_all() and generates scaffold hook
 * documentation for hook names that have the module's name as a prefix.
 */
class API extends PHPFile {

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return $this->component_data['root_component_name'] . '.api.php';
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    // We have no subcomponents.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function detectExistence(DrupalExtension $extension) {
    $this->exists = $extension->hasFile('%module.api.php');

    if (!$this->exists) {
      return;
    }

    $ast = $extension->getFileAST('%module.api.php');
    $ast = $extension->getASTFunctions($ast);

    // The first function in the AST will have additional comment blocks for the
    // @file and the @addtogroup tags. Remove these, since we will generate
    // them ourselves.
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
    $module_root_name = $this->component_data->root_component_name->value;

    return [
      'path' => '', // Means base folder.
      'filename' => "$module_root_name.api.php",
      'body' => $this->fileContents(),
      'build_list_tags' => ['code', 'api'],
    ];
  }

  /**
   * Return the summary line for the file docblock.
   */
  function fileDocblockSummary() {
    return "Hooks provided by the %readable module.";
  }

  /**
   * Return the main body of the file code.
   */
  function code_body() {
    // Sanity checks already done at this point; no need to catch exception.
    $mb_task_handler_analyze = \DrupalCodeBuilder\Factory::getTask('AnalyzeModule');

    $hooks = $mb_task_handler_analyze->getInventedHooks($this->component_data['root_component_name']);

    // Build an array of code pieces.
    $code_pieces = [];

    // The docblock grouping.
    $code_pieces['group'] = <<<EOT
      /**
       * @addtogroup hooks
       * @{
       */

      EOT;

    // Track the names of functions we generate.
    $generated_function_names = [];

    foreach ($hooks as $hook_short_name => $parameters) {
      $code_pieces['hook_' . $hook_short_name] = $this->hook_code($hook_short_name, $parameters);

      $generated_function_names['hook_' . $hook_short_name] = TRUE;
    }

    // Add lines from child function components.
    // Function data has been set by buildComponentContents().
    foreach ($this->functions as $function_name => $function_lines) {
      // Blank line after the function.
      $function_lines[] = '';

      $code_pieces[$function_name] = implode("\n", $function_lines);

      $generated_function_names[$function_name] = TRUE;
    }

    // Merge any existing functions.
    if ($this->exists) {
      // TODO! doesn't handle imports at top of existing file!
      // Add functions from the existing file, unless we are generating them
      // too, in which case we assume that our version is better.
      foreach ($this->existing as $function_node) {
        $existing_function_name = (string) $function_node->name;

        // Skip if the function has already been generated.
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

        $code_pieces[$existing_function_name] = implode("\n", $this->extension->getFileLines('%module.api.php', $first_line, $end_line));
      }
    }

    // The docblock grouping.
    $code_pieces['end_group'] = <<<EOT
      /**
       * @} End of "addtogroup hooks".
       */

      EOT;

    return $code_pieces;
  }

  /**
   * Create the code for a single hook.
   *
   * @param $hook_short_name
   *  The short name of the hook, i.e., without the 'hook_' prefix.
   * @param $parameters_string
   *  A string of the hook's parameters.
   *
   * @return
   *  A string of formatted code for inclusion in the api.php file.
   */
  function hook_code($hook_short_name, $parameters_string) {
    $parameters = explode(', ', $parameters_string);
    $parameters_doc_lines = [];
    foreach ($parameters as $parameter) {
      $parameters_doc_lines[] = " * @param $parameter\n" .
                                " *   TODO: document this parameter.";
    }
    if (!empty($parameters_doc_lines)) {
      $parameters_doc = " *\n" . implode("\n", $parameters_doc_lines);
    }

    return <<<EOT
      /**
       * TODO: write summary line.
       *
       * TODO: longer description.
      $parameters_doc
       *
       * @return
       *   TODO: Document return value if there is one.
       */
      function hook_$hook_short_name($parameters_string) {
        // TODO: write sample code.
      }

      EOT;
  }

}
