<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module info data for Drupal 5.
 */
class InfoModule5 extends InfoModule6 {

  /**
   * Create lines of file body for Drupal 5.
   */
  function infoData(): array {
    $lines = [];
    $lines['name'] =  $this->component_data['readable_name'];
    $lines['description'] =  $this->component_data['short_description'];

    if (!empty( $this->component_data['module_dependencies'])) {
      $lines['dependencies'] = implode(' ',  $this->component_data['module_dependencies']);
    }

    if (!empty( $this->component_data['module_package'])) {
      $lines['package'] =  $this->component_data['module_package'];
    }

    return $lines;
  }

}
