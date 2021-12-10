<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Drupal 7 version of component.
 */
class Module7 extends Module8 {

  /**
   * {@inheritdoc}
   */
  public static function configurationDefinition(): PropertyDefinition {
    return PropertyDefinition::create('complex');
  }

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->removeProperty('plugins');
    $definition->removeProperty('plugin_types');
    $definition->removeProperty('services');
    $definition->removeProperty('service_provider');
    $definition->removeProperty('phpunit_tests');
    $definition->getProperty('tests')->setDescription('');
    $definition->removeProperty('config_entity_types');
    $definition->removeProperty('drush_commands');

    // TODO: implement these for D7.
    $definition->removeProperty('content_entity_types');
    $definition->removeProperty('theme_hooks');
    $definition->removeProperty('forms');

    $definition->addProperties([
      'router_items' => static::getLazyDataDefinitionForGeneratorType('RouterItem', 'string')
        ->setLabel("Menu paths")
        ->setDescription("Paths for hook_menu(), eg 'path/foo'")
        ->setMultiple(TRUE),
      'settings_form' => static::getLazyDataDefinitionForGeneratorType('AdminSettingsForm', 'boolean')
        ->setLabel("Admin settings form")
        ->setDescription("A form for setting the module's general settings. Also produces a permission and a menu item."),
    ]);

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function rootComponentPropertyDefinitionAlter(PropertyDefinition $definition): void {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // On D7 and lower, modules need a .module file, even if empty.
    $components['%module.module'] = [
      'component_type' => 'ModuleCodeFile',
      'filename' => '%module.module',
    ];

    return $components;
  }

}
