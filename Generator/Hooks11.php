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
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'hook_implementation_type' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function addHookComponents(array &$components, array $hook_info): void {
    // Determine whether to switch the generators to the class method hook
    // implementations versions.
    // Hooks that go in the .install file are always procedural.
    if ($hook_info['destination'] == '%module.install') {
      parent::addHookComponents($components, $hook_info);
      return;
    }

    // Other random hooks that aren't documented as such are always procedural.
    if (in_array($hook_info['name'], static::PROCEDURAL_HOOKS)) {
      parent::addHookComponents($components, $hook_info);
      return;
    }

    // If the hook implementation type is set to procedural, then it's
    // procedural.
    dump($this->component_data->hook_implementation_type->value);
    if ($this->component_data->hook_implementation_type->value == 'procedural') {
      // arGH no this fucks up because it gets back to getHookImplementationComponentType!!
      parent::addHookComponents($components, $hook_info);
      return;
    }

    // If we're still here, make a class method hook.
    $hook_class_name = $this->getHookImplementationComponentType($hook_info);

    // Change to a class method generator. Which class we switch to depends on
    // which class the method returned.
    // TODO refactor!
    if ($hook_class_name == 'HookImplementation') {
      $hook_class_name = 'HookImplementationClassMethod';
    }
    else {
      // Specialised hook generators.
      $hook_class_name .= 'ClassMethod';
    }

    $components[$hook_info['name'] . '_method'] = [
      'component_type' => $hook_class_name,
      'code_file' => $hook_info['destination'],
      'hook_name' => $hook_info['name'],
      'declaration' => $hook_info['definition'],
      'description' => $hook_info['description'],
      // Set the hook template as the method body.
      'body' => $hook_info['template'],
      // The code is a single string, already indented. Ensure we don't
      // indent it again.
      'body_indented' => TRUE,
    ];

    // If we want legacy procedural hooks too.
    if ($this->component_data->hook_implementation_type->value == 'oo_legacy') {
      // Add the procedural hook.
      parent::addHookComponents($components, $hook_info);

      $components[$hook_info['name']]['attribute'] = 'Drupal\Core\Hook\LegacyHook';

      // todo legacy hook attribute
      // todo replace the hook body

      // todo service
      // $components

    }
  }

  /**
   * {@inheritdoc}
   */
  // KILL
  protected function XXXgetHookImplementationComponentType(array $hook_info): string {
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


