<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Drupal 7 version of component.
 */
class Module7 extends Module {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $component_data_definition = parent::componentDataDefinition();

    unset($component_data_definition['plugins']);
    unset($component_data_definition['plugins_yaml']);
    unset($component_data_definition['plugin_types']);
    unset($component_data_definition['services']);
    unset($component_data_definition['phpunit_tests']);
    unset($component_data_definition['tests']['description']);
    unset($component_data_definition['config_entity_types']);

    // TODO: implement these for D7.
    unset($component_data_definition['content_entity_types']);
    unset($component_data_definition['theme_hooks']);
    unset($component_data_definition['forms']);

    $component_data_definition['router_items'] = [
      'label' => "Menu paths",
      'description' => "Paths for hook_menu(), eg 'path/foo'",
      'required' => FALSE,
      'format' => 'array',
      'component_type' => 'RouterItem',
    ];

    return $component_data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    // On D7 and lower, modules need a .module file, even if empty.
    $components['%module.module'] = [
      'component_type' => 'ModuleCodeFile',
      'filename' => '%module.module',
    ];

    return $components;
  }

}
