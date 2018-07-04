<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module info file for Drupal 6.
 */
class Info6 extends InfoIni {

  /**
   * Create lines of file body for Drupal 6.
   */
  function file_body() {
    $lines = array();
    $lines['name'] =  $this->component_data['readable_name'];
    $lines['description'] =  $this->component_data['short_description'];
    if (!empty( $this->component_data['module_dependencies'])) {
      // For lines which form a set with the same key and array markers,
      // simply make an array.
      foreach ( $this->component_data['module_dependencies'] as $dependency) {
        $lines['dependencies'][] = $dependency;
      }
    }

    if (!empty( $this->component_data['module_package'])) {
      $lines['package'] =  $this->component_data['module_package'];
    }
    $lines['core'] = "6.x";

    $info = $this->process_info_lines($lines);
    return $info;
  }

}
