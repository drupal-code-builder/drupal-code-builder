<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Module7.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Drupal 7 version of component.
 */
class Module7 extends Module {

  /**
   * {@inheritdoc}
   */
  protected static function componentDataDefinition() {
    $component_data_definition = parent::componentDataDefinition();

    unset($component_data_definition['plugins']);
    unset($component_data_definition['services']);
    // TODO: implement these for D7.
    unset($component_data_definition['theme_hooks']);
    unset($component_data_definition['forms']);

    return $component_data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    // On D7 and lower, modules need a .module file, even if empty.
    $components['%module.module'] = 'ModuleCodeFile';

    return $components;
  }

}
