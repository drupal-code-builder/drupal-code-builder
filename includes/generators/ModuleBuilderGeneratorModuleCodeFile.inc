<?php

/**
 * @file
 * Contains generator classes for module code files.
 */

/**
 * Generator class for module code files.
 *
 * TODO: various cleanups. This was the base class of the mk 1 OO generator
 * system, and is currently (hello!) being forced into a new, bigger mk
 * hierarchy!
 */
class ModuleBuilderGeneratorModuleCodeFile extends ModuleBuilderGeneratorFile {

  // TODO: declare properties that are special!

  /**
   * Build the code files.
   */
  function collectFiles(&$files) {
    $files[$this->name] = array(
      'path' => '', // Means base folder.
      'filename' => $this->filename,
      'body' => $this->file_contents(),
      // We join code files up on a single newline. This means that each
      // component is responsible for ending its own lines.
      'join_string' => "\n",
    );
  }

  /**
   * Make the doxygen header for a given hook.
   * This does not return with an initial newline so the doc block may be
   * inserted into existing code.
   *
   * @param
   *   The long hook name, eg 'hook_menu'.
   */
  function hook_doxygen($hook_name) {
    return <<<EOT
/**
 * Implements $hook_name().
 */

EOT;
  }

  /**
   * Return the main body of the file code.
   */
  function code_body() {
    // Get old style variable names.
    $module_data = $this->component_data;
    $hook_data = $this->hook_data;

    // Build up an array of functions' code.
    $functions = array();
    foreach ($hook_data as $hook_name => $hook) {

      // Display PHP doc, using the original case of the hook name.
      $hook_code = '';
      $hook_code .= $this->hook_doxygen($hook['name']);

      // function declaration: put in the module name, add closing brace, decode html entities
      $declaration = preg_replace('/(?<=function )hook/', $module_data['module_root_name'], $hook['declaration']);
      $declaration .= ' {';
      $hook_code .= htmlspecialchars_decode($declaration);

      // See if function bodies exist; if so, use function bodies from template
      if (isset($hook['template'])) {
        // Strip out INFO: comments for advanced users
        if (!variable_get('module_builder_detail', 0)) {
          // Used to strip INFO messages out of generated file for advanced users.
          $pattern = '#\s+/\* INFO:(.*?)\*/#ms';
          $hook['template'] = preg_replace($pattern, '', $hook['template']);
        }
        //dsm($hook);

        $hook_code .= $hook['template'];
      }
      else {
        $hook_code .= "\n\n";
      }
      $hook_code .= "}\n";

      // Replace variables
      $variables = $this->getReplacements();
      $hook_code = strtr($hook_code, $variables);

      $functions[$hook_name] = $hook_code;
    } // foreach hook

    return $functions;
  }

  /**
   * Return a file footer.
   */
  function code_footer() {
    $footer = variable_get('module_builder_footer', '');
    return $footer;
  }

  /**
   * Helper to get replacement strings for tokens in code body.
   *
   * @return
   *  An array of tokens to replacements, suitable for use by strtr().
   */
  function getReplacements() {
    // Get old style variable names.
    $module_data = $this->component_data;

    return array(
      '%module'       => $module_data['module_root_name'],
      '%description'  => str_replace("'", "\'", $module_data['module_short_description']),
      '%name'         => !empty($module_data['module_readable_name']) ? str_replace("'", "\'", $module_data['module_readable_name']) : $module_data['module_root_name'],
      '%help'         => !empty($module_data['module_help_text']) ? str_replace('"', '\"', $module_data['module_help_text']) : t('TODO: Create admin help text.'),
      '%readable'     => str_replace("'", "\'", $module_data['module_readable_name']),
    );
  }

}

/**
 * Generator class for module code files for Drupal 6.
 */
class ModuleBuilderGeneratorModuleCodeFile6 extends ModuleBuilderGeneratorModuleCodeFile {
  /**
   * Make the doxygen header for a given hook with the Drupal 6 format.
   *
   * This does not return with an initial newline so the doc block may be
   * inserted into existing code.
   *
   * @param
   *   The long hook name, eg 'hook_menu'.
   */
  function hook_doxygen($hook_name) {
    return <<<EOT
/**
 * Implementation of $hook_name().
 */

EOT;
  }
}
