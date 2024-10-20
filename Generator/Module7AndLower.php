<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\DeferredGeneratorDefinition;
use DrupalCodeBuilder\Attribute\DrupalCoreVersion;
use DrupalCodeBuilder\Attribute\RelatedBaseClass;

/**
 * Module generator for Drupal versions 5, 6, and 7.
 */
#[DrupalCoreVersion(7)]
#[DrupalCoreVersion(6)]
#[DrupalCoreVersion(5)]
#[RelatedBaseClass('Module')]
class Module7AndLower extends Module8 {

  /**
   * {@inheritdoc}
   */
  public static function configurationDefinition(): PropertyDefinition {
    return PropertyDefinition::create('complex');
  }

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->removeProperty('plugins');
    $definition->removeProperty('plugin_types');
    $definition->removeProperty('services');
    $definition->removeProperty('event_subscribers');
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
      'router_items' => DeferredGeneratorDefinition::createFromGeneratorType('RouterItem', 'string')
        ->setLabel("Menu paths")
        ->setDescription("Paths for hook_menu(), eg 'path/foo'")
        ->setMultiple(TRUE),
      'settings_form' => DeferredGeneratorDefinition::createFromGeneratorType('AdminSettingsForm', 'boolean')
        ->setLabel("Admin settings form")
        ->setDescription("A form for setting the module's general settings. Also produces a permission and a menu item."),
    ]);
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
      'component_type' => 'ExtensionCodeFile',
      'filename' => '%module.module',
    ];

    return $components;
  }

}
