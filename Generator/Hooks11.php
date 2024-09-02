<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Hooks component for Drupal 11.
 *
 * This is a bit of a special case, as normally class inheritance is higher
 * versions as the parent class. But here 11 is a weird case as it needs to
 * potentially switch the HookImplementation component type.
 */
class Hooks11 extends Hooks {

  /**
   * {@inheritdoc}
   */
  protected function getHookImplementationComponentType(array $hook_info): string {
    $hook_class_name = parent::getHookImplementationComponentType($hook_info);

    // Determine whether to switch the generators to the class method hook
    // implementations versions.
    // Hooks that go in the .install file are always procedural.
    if ($hook_info['destination'] != '%module.install') {
      // Only generate hooks if the configuration is set.
      if (Hooks::$hook_implementation_type == 'oo') {
        if ($hook_class_name == 'HookImplementation') {
          $hook_class_name = 'HookImplementationClassMethod';
        }
        else {
          // Specialised hook generators.
          $hook_class_name .= 'ClassMethod';
        }
      }
    }

    return $hook_class_name;
  }

}


