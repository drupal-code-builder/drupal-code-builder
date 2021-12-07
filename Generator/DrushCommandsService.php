<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * NO! Generator for a Drush command.
 *
 * Needed??
 */
class DrushCommandsService extends Service {

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    // Although there can be more than one command class, it's typical to have
    // only one, so merge them.
    return 'drush-commands';
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    // Move the YAML file.
    $yaml_file_component = $components['%module.services.yml'];
    unset($components['%module.services.yml']);

    $yaml_file_component['filename'] = 'drush.services.yml';
    $yaml_file_component['yaml_data']['tags'][] = [
      'name' => 'drush.command',
    ];

    return $components;
  }

}
