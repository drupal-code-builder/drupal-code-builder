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
  function subComponents() {
    // We have no subcomponents.
    return array();
  }

  /**
   * Build the code files.
   */
  function collectFiles(&$files) {
    $module_root_name = $this->component_data['module_root_name'];

    // The key is arbitrary (at least so far!).
    $files['module.api.php'] = array(
      'path' => '', // Means base folder.
      'filename' => "$module_root_name.api.php",
      'body' => $this->file_contents(),
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
    // Get the module's folder.
    // module_folder
    $module_folder = $this->component_data['module_folder'];
    //$files = array_diff(scandir($module_folder), array('..', '.'));

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

    $module_root_name = $this->component_data['module_root_name'];
    $module_root_name_title_case = ucfirst($this->component_data['module_root_name']);
    $module_readable_name = $this->component_data['module_readable_name'];

    // Build an array of code pieces.
    $code_pieces = array();

    // The docblock grouping.
    $code_pieces[] = <<<EOT
/**
 * @addtogroup hooks
 * @{
 */
EOT;

    foreach ($hooks as $hook_short_name => $parameters) {
      $code_pieces[] = $this->hook_code($hook_short_name, $parameters);
    }

    return implode("\n\n", $code_pieces);
  }

  /**
   * Create the code for a single hook.
   *
   * @param $hook_short_name
   *  The short name of the hook, i.e., without the 'hook_' prefix.
   * @param $parameters
   *  A string of the hook's parameters.
   *
   * @return
   *  A string of formatted code for inclusion in the api.php file.
   */
  function hook_code($hook_short_name, $parameters) {
    return <<<EOT
/**
 * TODO: write summary line
 */
function hook_$hook_short_name($parameters) {
  // TODO: write sample code.
}
EOT;
  }

}
