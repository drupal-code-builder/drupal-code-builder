<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for class methods.
 *
 * @deprecated
 *
 * TODO: replace this with PHPFunction throughout.
 */
class PHPMethod extends PHPFunction {

  /**
   * Return a unique ID for this component.
   *
   * @return
   *  The unique ID
   */
  public function getUniqueID() {
    // Include the code file, as method names are not unique.
    return
      $this->component_data['root_component_name'] . '/' .
      implode(':', [$this->type, $this->component_data['code_file'], $this->name]);
  }

}
