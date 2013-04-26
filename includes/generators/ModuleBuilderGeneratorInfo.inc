<?php

/**
 * @file
 * Contains generator classes for .info files.
 */

/**
 * Generator base class for module info file.
 */
class ModuleBuilderGeneratorInfo extends ModuleBuilderGeneratorFile {

  /**
   * Build the code files.
   */
  function collectFiles(&$files) {
    $files['info'] = array(
      'path' => '', // Means base folder.
      'filename' => $this->component_data['module_root_name'] . '.info',
      'body' => array(
        $this->code_body(),
      ),
    );
  }

  /**
   * Override as info files have no header.
   */
  function file_header() { }

  /**
   * Override as info files have no header.
   */
  function code_header($filename = NULL) {
  }

  // Override abstract...
  function code_body() {}

  /**
   * Merge an array of info file lines into a single piece of text.
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
   *  The merged text suitable for output into a .info file.
   */
  function merge_info_lines($lines) {
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
    $info = implode("\n", $merged_lines);
    // Add a newline to the final line.
    $info .= "\n";
    return $info;
  }
}

/**
 * Generator class for module info file for Drupal 5.
 */
class ModuleBuilderGeneratorInfo5 extends ModuleBuilderGeneratorInfo {
  function code_body() {
    $module_data = $this->component_data;

    $lines = array();
    $lines['name'] = $module_data['module_readable_name'];
    $lines['description'] = $module_data['module_short_description'];

    if (!empty($module_data['module_dependencies'])) {
      $lines['dependencies'] = $module_data['module_dependencies'];
    }

    if (!empty($module_data['module_package'])) {
      $lines['package'] = $module_data['module_package'];
    }

    $info = $this->merge_info_lines($lines);
    return $info;
  }
}

/**
 * Generator class for module info file for Drupal 6.
 */
class ModuleBuilderGeneratorInfo6 extends ModuleBuilderGeneratorInfo {
  function code_body() {
    $module_data = $this->component_data;

    $lines = array();
    $lines['name'] = $module_data['module_readable_name'];
    $lines['description'] = $module_data['module_short_description'];
    if (!empty($module_data['module_dependencies'])) {
      // For lines which form a set with the same key and array markers,
      // simply make an array.
      foreach (explode(' ', $module_data['module_dependencies']) as $dependency) {
        $lines['dependencies'][] = $dependency;
      }
    }

    if (!empty($module_data['module_package'])) {
      $lines['package'] = $module_data['module_package'];
    }
    $lines['core'] = "6.x";

    $info = $this->merge_info_lines($lines);
    return $info;
  }
}

/**
 * Generator class for module info file for Drupal 7.
 */
class ModuleBuilderGeneratorInfo7 extends ModuleBuilderGeneratorInfo {
  function code_body() {
    $module_data = $this->component_data;
    //print_r($module_data);

    $lines = array();
    $lines['name'] = $module_data['module_readable_name'];
    $lines['description'] = $module_data['module_short_description'];
    if (!empty($module_data['module_dependencies'])) {
      // For lines which form a set with the same key and array markers,
      // simply make an array.
      foreach (explode(' ', $module_data['module_dependencies']) as $dependency) {
        $lines['dependencies'][] = $dependency;
      }
    }

    if (!empty($module_data['module_package'])) {
      $lines['package'] = $module_data['module_package'];
    }

    $lines['core'] = "7.x";

    /*
    // Only files containing classes need to be declared in the .info file;
    // we don't add any classes yet.
    if (is_array($module_data['module_files'])) {
      foreach ($module_data['module_files'] as $file) {
        $lines['files'][] = $file;
      }
    }
    */

    $info = $this->merge_info_lines($lines);
    return $info;
  }
}
