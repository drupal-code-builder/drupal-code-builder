<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module info file for Drupal 8.
 */
class Info8 extends Info {

  /**
   * {@inheritdoc}
   */
  protected static $propertiesAcquiredFromRoot = [
    'readable_name',
    'short_description',
    'module_dependencies',
    'module_package',
  ];

  /**
   * {@inheritdoc}
   */
  function infoData(): array {
    $lines = parent::infoData();

    $lines['core'] = "8.x";

    return $lines;
  }

}
