<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator base class for module README file.
 *
 * (You were going to write a README file, right?)
 */
class Readme extends File {

  /**
   * Collect the code files.
   */
  public function getFileInfo() {
    return array(
      'path' => '', // Means the base folder.
      // The extension is in lowercase for good reasons which I don't remember
      // right now, but probably to do with Windows being rubbish.
      'filename' => 'README.txt',
      'body' => $this->lines(),
      // We are returning single lines, so they need to be joined with line
      // breaks.
      'join_string' => "\n",
      'build_list_tags' => ['readme'],
    );
  }

  /**
   * Return an array of lines.
   *
   * @return
   *  An array of lines of text.
   */
  function lines() {
    return array(
      $this->root_component->component_data['readable_name'],
      str_repeat('=', strlen($this->root_component->component_data['readable_name'])),
      '',
      'TODO: write some documentation.',
      '',
    );
  }

}
