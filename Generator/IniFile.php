<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\File\CodeFile;
use Ckr\Util\ArrayMerger;
use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for ini files used for pre-D8 info files.
 */
class IniFile extends File {

  /**
   * {@inheritdoc}
   */
  public function getFileInfo(): CodeFile {
    $ini_data = [];
    foreach ($this->containedComponents['element'] as $key => $child_item) {
      $child_item_data = $child_item->getContents();

      // Use array merge as child items may provide numerically-keyed lists,
      // which should not clobber each other.
      $ini_data = ArrayMerger::doMerge($ini_data, $child_item_data);
    }

    $file_info = new CodeFile($this->processInfoLines($ini_data));

    return $file_info;
  }

  /**
   * Process a structured array of info files lines to a flat array for merging.
   *
   * @param $lines
   *  An array of lines keyed by label.
   *  Place grouped labels (eg, dependencies) into an array of
   *  their own, keyed numerically.
   *  Eg:
   *    name => module name
   *    dependencies => array(foo, bar)
   *
   * @return
   *  An array of lines for the .info file.
   */
  function processInfoLines($lines) {
    foreach ($lines as $label => $data) {
      if (is_array($data)) {
        foreach ($data as $data_piece) {
          $merged_lines[] = $label . "[] = $data_piece"; // Urgh terrible variable name!
        }
      }
      elseif ($data == '') {
        // An empty data value means a blank line.
        $merged_lines[] = '';
      }
      else {
        $merged_lines[] = "$label = $data";
      }
    }

    // Add final empty line so the file has a closing linebreak.
    $merged_lines[] = '';

    //drush_print_r($merged_lines);
    return $merged_lines;
  }

}
