<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\InfoIni.
 */

namespace ModuleBuilder\Generator;

/**
 * Abstract parent class for .ini syntax info files.
 */
class InfoIni extends Info {

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
  function process_info_lines($lines) {
    foreach ($lines as $label => $data) {
      if (is_array($data)) {
        foreach ($data as $data_piece) {
          $merged_lines[] = $label . "[] = $data_piece"; // Urgh terrible variable name!
        }
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
