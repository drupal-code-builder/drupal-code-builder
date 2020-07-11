<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator;

use DrupalCodeBuilder\Generator\GeneratorInterface;

/**
 *  TODO.
 */
class SimpleGenerator implements GeneratorInterface {

  public function getMergeTag() {
    return NULL;
  }

  public function requiredComponents() {
    return [];
  }

  public function getType() {
    return 'my_root';
  }

}
