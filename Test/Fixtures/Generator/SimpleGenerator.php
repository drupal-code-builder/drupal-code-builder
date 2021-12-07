<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator;

use DrupalCodeBuilder\Generator\GeneratorInterface;

/**
 * Dummy generator class for tests.
 */
class SimpleGenerator implements GeneratorInterface {

  /**
   * Quick hack so we can use the same class for everything.
   *
   * @var string
   */
  public $componentType;

  public $component_data;

  public function getMergeTag() {
    return NULL;
  }

  public function requiredComponents(): array {
    return [];
  }

  public function getType() {
    return $this->componentType;
  }

  public function isRootComponent(): bool {
    return ($this->componentType == 'my_root');
  }

}
