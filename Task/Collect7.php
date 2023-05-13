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

  /**
   * Override the parent method to not have the injection attribute.
   *
   * This class uses constructor injection as it only uses one collector.
   */
  public function setCollectors(array $collectors) {
  }

}
