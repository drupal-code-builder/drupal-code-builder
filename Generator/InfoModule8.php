<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module info data for Drupal 8.
 */
class InfoModule8 extends InfoModule {

  /**
   * {@inheritdoc}
   */
  protected static $propertiesAcquiredFromRoot = [
    'base',
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
