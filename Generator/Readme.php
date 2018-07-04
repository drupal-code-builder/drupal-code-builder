<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator base class for module README file.
 *
 * (You were going to write a README file, right?)
 */
class Readme extends File {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'readable_name' => [
        'acquired' => TRUE,
      ],
    ];
  }

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
      $this->component_data['readable_name'],
      str_repeat('=', strlen($this->component_data['readable_name'])),
      '',
      'TODO: write some documentation.',
      '',
    );
  }

}
