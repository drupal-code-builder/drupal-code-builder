<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for the service which defines Drush commands.
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
  public function requiredComponents(): array {
    // This clobbers any other tags but we shouldn't need any. Setting this as
    // a literal default in getPropertyDefinition() causes a PHP nesting level
    // error. Not clobbering here causes duplication somehow.
    $this->component_data->tags = [
      0 => [
        'name' => 'drush.command',
      ],
    ];

    $components = parent::requiredComponents();

    // Move and tweak the YAML file.
    $yaml_file_component = $components['%module.services.yml'];
    unset($components['%module.services.yml']);

    $yaml_file_component['filename'] = 'drush.services.yml';

    $components['drush.services.yml'] = $yaml_file_component;

    return $components;
  }

}
