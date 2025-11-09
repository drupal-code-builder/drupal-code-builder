<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Interface for generators which get the environment injected.
 *
 * Generators implementing this interface have the setEnvironment() method
 * called by the class handler.
 *
 * @see \DrupalCodeBuilder\Generator\EnvironmentAwareTrait
 * @see \DrupalCodeBuilder\Task\Generate\ComponentClassHandler
 */
interface EnvironmentAware {

  /**
   * Sets the environment.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment.
   */
  public function setEnvironment(EnvironmentInterface $environment): void;

}
