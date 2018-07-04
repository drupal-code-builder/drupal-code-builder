<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module info file for Drupal 5.
 */
class Info5 extends InfoIni {

  /**
   * Create lines of file body for Drupal 5.
   */
  function file_body() {
    $lines = array();
    $lines['name'] =  $this->component_data['readable_name'];
    $lines['description'] =  $this->component_data['short_description'];

    if (!empty( $this->component_data['module_dependencies'])) {
      $lines['dependencies'] = implode(' ',  $this->component_data['module_dependencies']);
    }

    if (!empty( $this->component_data['module_package'])) {
      $lines['package'] =  $this->component_data['module_package'];
    }

    $info = $this->process_info_lines($lines);
    return $info;
  }

}
