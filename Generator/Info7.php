<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Info7.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator class for module info file for Drupal 7.
 */
class Info7 extends InfoIni {

  /**
   * Create lines of file body for Drupal 7.
   */
  function file_body() {
    $args = func_get_args();
    $files = array_shift($args);

    $module_data = $this->base_component->component_data;
    //print_r($module_data);

    $lines = array();
    $lines['name'] = $module_data['readable_name'];
    $lines['description'] = $module_data['short_description'];
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

    // Files containing classes need to be declared in the .info file.
    foreach ($files as $file) {
      if (!empty($file['contains_classes'])) {
        $lines['files'][] = $file['filename'];
      }
    }

    $info = $this->process_info_lines($lines);
    return $info;
  }

}
