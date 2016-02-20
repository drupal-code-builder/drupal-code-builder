<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Readme.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator base class for module README file.
 *
 * (You were going to write a README file, right?)
 */
class Readme extends File {

  /**
   * {@inheritdoc}
   */
  public static function requestedComponentHandling() {
    return 'singleton';
  }

  /**
   * Collect the code files.
   */
  public function getFileInfo() {
    $files['readme'] = array(
      'path' => '', // Means the base folder.
      // The extension is in lowercase for good reasons which I don't remember
      // right now, but probably to do with Windows being rubbish.
      'filename' => 'README.txt',
      'body' => $this->lines(),
      // We are returning single lines, so they need to be joined with line
      // breaks.
      'join_string' => "\n",
    );
    return $files;
  }

  /**
   * Return an array of lines.
   *
   * @return
   *  An array of lines of text.
   */
  function lines() {
    return array(
      $this->base_component->component_data['readable_name'],
      str_repeat('=', strlen($this->base_component->component_data['readable_name'])),
      '',
      'TODO: write some documentation.',
      '',
    );
  }

}
