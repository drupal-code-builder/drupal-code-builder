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
  protected function getHookImplementationComponentType(string $hook_name): string {
    $hook_class_name = parent::getHookImplementationComponentType($hook_name);

    if (Hooks::$hook_implementation_type == 'oo') {
      if ($hook_class_name == 'HookImplementation') {
        $hook_class_name = 'HookImplementationClassMethod';
      }
      else {
        // Specialised hook generators.
        $hook_class_name .= 'ClassMethod';
      }
    }

    return $hook_class_name;
  }

}


