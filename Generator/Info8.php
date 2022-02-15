<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module info file for Drupal 8.
 */
class Info8 extends Info9 {

  /**
   * {@inheritdoc}
   */
  function infoData(): array {
    $lines = parent::infoData();

    $lines['core'] = "8.x";

    return $lines;
  }

}
