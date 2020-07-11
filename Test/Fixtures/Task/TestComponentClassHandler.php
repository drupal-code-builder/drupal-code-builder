<?php

namespace DrupalCodeBuilder\Test\Fixtures\Task;

use DrupalCodeBuilder\Task\Generate\ComponentClassHandler;

/**
 *  Task TODO helper for working with generator classes and instantiating them.
 */
class TestComponentClassHandler extends ComponentClassHandler {

  protected $map = [];

  // YAGNI?
  public function setClassMap(array $map) {
    $this->map = $map;
  }

  /**
   * TODO
   */
  public function getGenerator($component_type, $component_data = NULL) {
    if (!isset($this->map[$component_type])) {
      $generator = new \DrupalCodeBuilder\Test\Fixtures\Generator\SimpleGenerator();
      // $generator->setType = $component_type;
    }
    return $generator;
  }

}
