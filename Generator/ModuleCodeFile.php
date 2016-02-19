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
   * Return a file footer.
   */
  function code_footer() {
    $footer = \ModuleBuilder\Factory::getEnvironment()->getSetting('footer', '');
    return $footer;
  }

}
