<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Trait for implementing the EnvironmentAware interface.
 */
trait EnvironmentAwareTrait {

  /**
   * The environment.
   */
  protected EnvironmentInterface $environment;

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment): void {
    $this->environment = $environment;
  }

}
