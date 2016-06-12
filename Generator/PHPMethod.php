<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\PHPMethod.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for class methods.
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
    return implode(':', [$this->type, $this->code_file, $this->name]);
  }

}
