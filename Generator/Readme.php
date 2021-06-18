<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator base class for module README file.
 *
 * (You were going to write a README file, right?)
 */
class Readme extends File {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'readable_name' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);

    return $definition;
  }

  /**
   * Collect the code files.
   */
  public function getFileInfo() {
    return [
      'path' => '', // Means the base folder.
      // The extension is in lowercase for good reasons which I don't remember
      // right now, but probably to do with Windows being rubbish.
      'filename' => 'README.txt',
      'body' => $this->lines(),
      'build_list_tags' => ['readme'],
    ];
  }

  /**
   * Return an array of lines.
   *
   * @return
   *  An array of lines of text.
   */
  function lines() {
    return [
      $this->component_data['readable_name'],
      str_repeat('=', strlen($this->component_data['readable_name'])),
      '',
      'TODO: write some documentation.',
      '',
    ];
  }

}
