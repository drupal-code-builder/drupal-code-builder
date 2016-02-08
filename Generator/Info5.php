<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Info5.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator class for module info file for Drupal 5.
 */
class Info5 extends InfoIni {

  /**
   * Create lines of file body for Drupal 5.
   */
  function file_body() {
    $module_data = $this->base_component->component_data;

    $lines = array();
    $lines['name'] = $module_data['readable_name'];
    $lines['description'] = $module_data['short_description'];

    if (!empty($module_data['module_dependencies'])) {
      $lines['dependencies'] = implode(' ', $module_data['module_dependencies']);
    }

    if (!empty($module_data['module_package'])) {
      $lines['package'] = $module_data['module_package'];
    }

    $info = $this->process_info_lines($lines);
    return $info;
  }

}
