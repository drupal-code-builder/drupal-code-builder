<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Component generator: api.php file for documention hooks and callbacks.
 *
 * This component should be requested once module code has been written. It
 * looks for calls to module_invoke_all() and generates scaffold hook
 * documentation for hook names that have the module's name as a prefix.
 */
class API extends PHPFile {

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    // We have no subcomponents.
    return array();
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $module_root_name = $this->component_data['root_component_name'];

    $this->filename = "$module_root_name.api.php";

    return array(
      'path' => '', // Means base folder.
      'filename' => $this->filename,
      'body' => $this->fileContents(),
      'join_string' => "\n",
      'build_list_tags' => ['code', 'api'],
    );
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
    $code_pieces = array();

    // The docblock grouping.
    $code_pieces['group'] = <<<EOT
/**
 * @addtogroup hooks
 * @{
 */

EOT;

    foreach ($hooks as $hook_short_name => $parameters) {
      $code_pieces[$hook_short_name] = $this->hook_code($hook_short_name, $parameters);
    }

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
    $parameters_doc_lines = array();
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
