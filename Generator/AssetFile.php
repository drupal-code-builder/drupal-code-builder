<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Asset file generator.
 *
 * Most of the work is done in Library[CSS/JS]Asset.
 */
class AssetFile extends File  {

  /**
   * {@inheritdoc}
   */
  public function getFileInfo() {
    return [
      'path' => '', // Means base folder.
      'filename' => $this->component_data['filename'],
      'body' => [],
    ];
  }

}
