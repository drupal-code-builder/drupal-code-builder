<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for PHP interface files.
 *
 * TODO: extending from class file is hacky and will cause problems if we
 * expect too much of this.
 */
class PHPInterfaceFile extends PHPClassFile {

  /**
   * Produces the interface declaration.
   */
  function class_declaration() {
    $line = '';
    $line .= "interface $this->plain_class_name";
    if ($this->component_data['parent_class_name']) {
      // TODO! extends more than 1 interface!
      $line .= " extends {$this->component_data['parent_class_name']}";
    }
    $line .= ' {';

    return [
      $line,
    ];
  }

}
