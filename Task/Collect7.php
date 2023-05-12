<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\Task\Collect\HooksCollector;

/**
 * Task handler for collecting and processing component definitions.
 */
class Collect7 extends Collect {

  /**
   * Constructor.
   */
  function __construct(
    EnvironmentInterface $environment,
    HooksCollector $hooks_collector
  ) {
    $this->environment = $environment;

    $this->collectors = [
      'Collect\HooksCollector' => $hooks_collector,
    ];
  }

}
