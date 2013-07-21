<?php

/**
 * Component generator: api.php file for documention hooks and callbacks.
 *
 * This component should be requested once module code has been written. It
 * looks for calls to module_invoke_all() and generates scaffold hook
 * documentation for hook names that have the module's name as a prefix.
 */
class ModuleBuilderGeneratorAPI extends ModuleBuilderGeneratorFile {

  /**
   * Return an array of subcomponent types.
   */
  protected function subComponents() {
    // We have no subcomponents.
    return array();
  }

  /**
   * Build the code files.
   */
  function collectFiles(&$files) {
    $module_root_name = $this->component_data['module_root_name'];

    $this->filename = "$module_root_name.api.php";

    // The key is arbitrary (at least so far!).
    $files['module.api.php'] = array(
      'path' => '', // Means base folder.
      'filename' => $this->filename,
      'body' => $this->file_contents(),
      'join_string' => "\n",
    );
  }

  /**
   * Return the summary line for the file docblock.
   */
  function file_doc_summary() {
    $module_readable_name = $this->component_data['module_readable_name'];
    return "Hooks provided by the $module_readable_name module.";
  }

  /**
   * Return the main body of the file code.
   */
  function code_body() {
    $hooks = $this->get_existing_hooks();

    $module_root_name = $this->component_data['module_root_name'];
    $module_root_name_title_case = ucfirst($this->component_data['module_root_name']);
    $module_readable_name = $this->component_data['module_readable_name'];

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
 *  TODO: Document return value if there is one.
 */
function hook_$hook_short_name($parameters_string) {
  // TODO: write sample code.
}

EOT;
  }

  /**
   * Helper to get hooks from existing code files.
   *
   * @return
   *  An array of hooks and their parameters. The hooks are deduced from the
   *  calls to module_invoke_all(), and the probably parameters are taken from
   *  the variables passed to the call. The keys of the array are hook short
   *  names; the values are the parameters string, with separating commas but
   *  without the outer parentheses. E.g.:
   *    'foo_insert' => '$foo, $bar'
   */
  function get_existing_hooks() {
    // Get the module's folder.
    $module_folder = $this->component_data['module_folder'];

    // Bail if the folder doesn't exist yet: there is nothing to do.
    if (!file_exists($module_folder)) {
      return array();
    }

    // An array of short hook names that we'll populate from what we extract
    // from the files.
    $hooks = array();

    // Only consider hooks which are invented by this module. We assume the
    // module follows the convention of using its name as a prefix.
    $hook_prefix = $this->component_data['module_root_name'] . '_';

    // Recurse all files in the module folder. We might as well just look at all
    // files rather than try to cover all possible file extensions.
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($module_folder));
    foreach ($objects as $filename => $object) {
      $contents = file_get_contents($filename);

      $matches = array();
      preg_match_all("/module_invoke_all\('($hook_prefix\w+)'(?:,\s*([^)]*))?/", $contents, $matches);
      // Matches are:
      //  - 1: the first parameter, which is the hook short name.
      //  - 2: the remaining parameters, if any.

      // If we get matches, turn then into keyed arrays and merge them into
      // the cumulative array. This removes duplicates (caused by a hook being
      // invoked in different files).
      if (!empty($matches[1])) {
        //drush_print_r($matches);
        $file_hooks = array_combine($matches[1], $matches[2]);
        //drush_print_r($file_hooks);

        foreach ($file_hooks as $hook_short_name => $parameters) {
          // If this hook is already in our list, we take the longest parameters
          // string, on the assumption that this may be more complete if some
          // parameters are options.
          if (isset($hooks[$hook_short_name])) {
            // Replace the existing hook if the new parameters are longer.
            if (strlen($parameters) > strlen($hooks[$hook_short_name])) {
              $hooks[$hook_short_name] = $parameters;
            }
          }
          else {
            $hooks[$hook_short_name] = $parameters;
          }
        }
      }
    }
    //drush_print_r($hooks);

    return $hooks;
  }

}
