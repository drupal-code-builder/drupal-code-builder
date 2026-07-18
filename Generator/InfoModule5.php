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
    $lines['name'] =  $this->component_data->readable_name->value;
    $lines['description'] =  $this->component_data->short_description->value;

    if (!$this->component_data->module_dependencies->isEmpty()) {
      $lines['dependencies'] = implode(' ',  $this->component_data->module_dependencies->values());
    }

    if (!empty( $this->component_data->module_package->value)) {
      $lines['package'] =  $this->component_data->module_package->value;
    }

    return $lines;
  }

}
