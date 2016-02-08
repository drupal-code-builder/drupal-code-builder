<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Info6.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator class for module info file for Drupal 6.
 */
class Info6 extends InfoIni {

  /**
   * Create lines of file body for Drupal 6.
   */
  function file_body() {
    $module_data = $this->base_component->component_data;

    $lines = array();
    $lines['name'] = $module_data['readable_name'];
    $lines['description'] = $module_data['short_description'];
    if (!empty($module_data['module_dependencies'])) {
      // For lines which form a set with the same key and array markers,
      // simply make an array.
      foreach ($module_data['module_dependencies'] as $dependency) {
        $lines['dependencies'][] = $dependency;
      }
    }

    if (!empty($module_data['module_package'])) {
      $lines['package'] = $module_data['module_package'];
    }
    $lines['core'] = "6.x";

    $info = $this->process_info_lines($lines);
    return $info;
  }

}
