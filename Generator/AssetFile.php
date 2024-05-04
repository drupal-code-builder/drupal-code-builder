<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\File\CodeFile;

/**
 * Asset file generator.
 *
 * Most of the work is done in Library[CSS/JS]Asset.
 */
class AssetFile extends File  {

  /**
   * {@inheritdoc}
   */
  public function getFileInfo(): CodeFile {
    return new CodeFile([]);
  }

}
