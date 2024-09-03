<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Hooks component for Drupal 11.
 *
 * This is a bit of a special case, as normally class inheritance is higher
 * versions as the parent class. But here 11 is a weird case as it needs to
 * check the module configuration setting when determining whether to switch the
 * HookImplementation component type.
 *
 * Hooks12 and higher will have this logic for install hooks, but without the
 * configuration setting check.
 */
class Hooks11 extends Hooks {

  /**
   * Theme hooks which remain procedural.
   *
   * TODO: Move this to analysis? Although there's no sodding documentation.
   */
  const PROCEDURAL_HOOKS = [
    'hook_theme',
    'hook_theme_suggestion_HOOK',
    'hook_preprocess_hook',
    'hook_process_hook',
    'hook_theme_suggestions_HOOK_alter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getHookImplementationComponentType(array $hook_info): string {
    $hook_class_name = parent::getHookImplementationComponentType($hook_info);

    // Determine whether to switch the generators to the class method hook
    // implementations versions.
    // Hooks that go in the .install file are always procedural.
    if ($hook_info['destination'] == '%module.install') {
      return $hook_class_name;
    }

    if (in_array($hook_info['name'], static::PROCEDURAL_HOOKS)) {
      return $hook_class_name;
    }

    // Only generate hooks if the configuration is set.
    if (Hooks::$hook_implementation_type != 'oo') {
      return $hook_class_name;
    }

    // Which class we switch to depends on which class the parent method
    // returned.
    if ($hook_class_name == 'HookImplementation') {
      $hook_class_name = 'HookImplementationClassMethod';
    }
    else {
      // Specialised hook generators.
      $hook_class_name .= 'ClassMethod';
    }

    return $hook_class_name;
  }

}


