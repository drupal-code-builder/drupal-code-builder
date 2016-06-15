<?php

/**
 * @file
 * Contains generator classes for module PHP files.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module code files.
 */
class ModuleCodeFile extends PHPFile {

  /**
   * The name of the file this creates, which may include tokens.
   */
  protected $filename;

  /**
   * Constructor.
   *
   * @param $component_name
   *  The name should be the eventual filename, which may include tokens such as
   *  %module, which are handled by assembleFiles().
   * @param $component_data
   *   An array of data for the component.
   */
  function __construct($component_name, $component_data, $root_generator) {
    $this->filename = $component_name;

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $files[$this->filename] = array(
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
    $footer = \DrupalCodeBuilder\Factory::getEnvironment()->getSetting('footer', '');
    return $footer;
  }

}
