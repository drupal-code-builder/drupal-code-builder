<?php

/**
 * @file
 * Contains generator classes for module PHP files.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator class for module code files.
 */
class ModuleCodeFile extends PHPFile {

  // TODO: declare properties that are special!

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    // Our component name is our future filename. Tokens such as '%module' are
    // replaced by assembleFiles().
    $this->filename = $this->name;

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
