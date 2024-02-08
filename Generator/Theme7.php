<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;

/**
 * Drupal 7 version of component.
 */
class Theme7 extends Theme {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->removeProperty('base_theme');
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    return [];

    // TODO: restore this?

    $theme_data = $this->component_data;
    //drush_print_r($theme_data);

    drupal_theme_initialize();
    $theme_registry = theme_get_registry();

    $components = parent::requiredComponents();
    foreach ($this->component_data['themeables'] as $theme_hook_name) {
      $hook = $theme_hook_name;
      // Iteratively strip everything after the last '--' delimiter, until an
      // implementation is found.
      // (We use -- rather than __ because they're easier to type!)
      // TODO: allow both!
      while ($pos = strrpos($hook, '--')) {
        $hook = substr($hook, 0, $pos);
        if (isset($theme_registry[$hook])) {
          break;
        }
      }
      if (!isset($theme_registry[$hook])) {
        // Bad name. Skip it.
        continue;
      }
      //drush_print_r($hook);

      if (isset($theme_registry[$hook]['template'])) {
        $components[$theme_hook_name] = 'themeTemplate';

        // Store data about this theme hook that we've found.
        $this->component_data['theme_hook_bases'][$theme_hook_name] = $hook;
      }
      else {
        // Fall through, as 'function' is optional in hook_theme().
        // TODO: we don't do theme functions yet -- need a system to add code
        // to existing files!
        //$components[$theme_hook_name] = 'theme_function';
      }
    }

    //drush_print_r($components);
    return $components;
  }

}