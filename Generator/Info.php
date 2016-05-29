<?php

/**
 * @file
 * Contains generator classes for .info files.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator base class for module info file.
 */
class Info extends File {

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    $lines = array();
    foreach ($this->filterComponentContentsForRole($children_contents, 'infoline') as $component_name => $component_lines) {
      // Assume that children components don't tread on each others' toes and
      // provide the same property names.
      $lines += $component_lines;
    }

    // Temporary, until Generate handles the return from this.
    $this->extraLines = $lines;
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $files['info'] = array(
      'path' => '',
      'filename' => '%module.info',
      'body' => $this->file_body(),
      // We join the info lines with linebreaks, as they (currently!) do not
      // come with their own lineends.
      // TODO: fix this!
      'join_string' => "\n",
    );
    return $files;
  }

}
