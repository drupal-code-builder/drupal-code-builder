<?php

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
    // Create a build list tag from the filename.
    $filename_pieces = explode('.', $this->filename);
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

    return array(
      'path' => '', // Means base folder.
      'filename' => $this->filename,
      'body' => $this->fileContents(),
      // We join code files up on a single newline. This means that each
      // component is responsible for ending its own lines.
      'join_string' => "\n",
      'build_list_tags' => ['code', $file_key_tag],
    );
  }

  /**
   * Return a file footer.
   */
  function code_footer() {
    $footer = \DrupalCodeBuilder\Factory::getEnvironment()->getSetting('footer', '');
    return $footer;
  }

}
