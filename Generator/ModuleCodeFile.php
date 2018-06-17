<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module code files.
 */
class ModuleCodeFile extends PHPFile {

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return $this->component_data['filename'];
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    // Create a build list tag from the filename.
    $filename_pieces = explode('.', $this->component_data['filename']);
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
      'filename' => $this->component_data['filename'],
      'body' => $this->fileContents(),
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
