<?php

/**
 * @file
 * Contains generator classes for module PHP files.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator class for module code files.
 *
 * TODO: various cleanups. This was the base class of the mk 1 OO generator
 * system, and is currently (hello!) being forced into a new, bigger mk
 * hierarchy!
 */
class ModuleCodeFile extends PHPFile {

  // TODO: declare properties that are special!

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    // Our component name is our future filename, with the token '%module' to
    // be replaced.
    $this->filename = str_replace('%module', $this->base_component->component_data['root_name'], $this->name);

    $files[$this->name] = array(
      'path' => '', // Means base folder.
      'filename' => $this->filename,
      'body' => $this->file_contents(),
      // We join code files up on a single newline. This means that each
      // component is responsible for ending its own lines.
      'join_string' => "\n",
    );
    return $files;
  }

  /**
   * Make the doxygen header for a given hook.
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
 * Implements $hook_name().
 */

EOT;
  }

  /**
   * Return a file footer.
   */
  function code_footer() {
    $footer = \ModuleBuilder\Factory::getEnvironment()->getSetting('footer', '');
    return $footer;
  }

}
