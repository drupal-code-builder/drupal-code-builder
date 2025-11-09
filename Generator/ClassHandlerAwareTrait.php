<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Task\Generate\ComponentClassHandler;

trait ClassHandlerAwareTrait {

  /**
   * The class handler.
   *
   * @var \DrupalCodeBuilder\Task\Generate\ComponentClassHandler
   */
  protected ComponentClassHandler $classHandler;

  /**
   * {@inheritdoc}
   */
  public function setClassHandler(ComponentClassHandler $class_handler): void {
    $this->classHandler = $class_handler;
  }

}
