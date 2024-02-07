<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module info data for Drupal 7.
 */
class InfoModule7 extends InfoModule {

  /**
   * {@inheritdoc}
   */
  const INFO_COMPONENT_TYPE = 'IniFile';

  /**
   * {@inheritdoc}
   */
  const INFO_FILENAME = '%module.info';

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
   * Create lines of file body for Drupal 7.
   */
  function infoData(): array {
    $lines = $this->getInfoFileEmptyLines();
    $lines['name'] =  $this->component_data['readable_name'];
    $lines['description'] =  $this->component_data['short_description'];
    if (!empty( $this->component_data['module_dependencies'])) {
      // For lines which form a set with the same key and array markers,
      // simply make an array.
      foreach ( $this->component_data['module_dependencies'] as $dependency) {
        $lines['dependencies'][] = $dependency;
      }
    }

    if (!empty( $this->component_data['module_package'])) {
      $lines['package'] =  $this->component_data['module_package'];
    }

    $lines['core'] = "7.x";

    if (!empty($extra_lines = $this->getContainedComponentInfoLines())) {
      // Add a blank line before the extra lines.
      $lines[] = '';
      $lines = array_merge($lines, $extra_lines);
    }

    return $lines;
  }

}
