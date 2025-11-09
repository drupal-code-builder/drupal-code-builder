<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Task\Generate\ComponentClassHandler;

/**
 * Interface for generators which get the class handler injected.
 *
 * Generators implementing this interface have the setClassHandler() method
 * called by the class handler.
 *
 * @see \DrupalCodeBuilder\Generator\ClassHandlerAwareTrait
 * @see \DrupalCodeBuilder\Task\Generate\ComponentClassHandler
 */
interface ClassHandlerAware {

  /**
   * Sets the class handler.
   *
   * @param \DrupalCodeBuilder\Task\Generate\ComponentClassHandler $class_handler
   *   The class handler.
   */
  public function setClassHandler(ComponentClassHandler $class_handler): void;

}