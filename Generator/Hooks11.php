<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Hooks component for Drupal 11.
 *
 * This is a bit of a special case, as normally class inheritance is higher
 * versions as the parent class. But here 11 is a weird case as it needs to
 * check the hook_implementation_type property when determining whether to
 * switch the HookImplementation component type.
 *
 * Hooks12 and higher will have this logic for install hooks, but without the
 * hook_implementation_type property check.
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
    $use_procedural_hook = FALSE;

    // Determine whether to switch the generators to the class method hook
    // implementations versions.
    // Hooks that go in the .install file are always procedural.
    if ($hook_info['destination'] == '%module.install') {
      $use_procedural_hook = TRUE;
    }

    // Other random hooks that aren't documented as such are always procedural.
    if (in_array($hook_info['name'], static::PROCEDURAL_HOOKS)) {
      $use_procedural_hook = TRUE;
    }

    // If the hook implementation type is set to procedural, then it's
    // procedural.
    if ($this->component_data->hook_implementation_type->value == 'procedural') {
      $use_procedural_hook = TRUE;
    }

    // For a procedural hook, just use the parent method.
    if ($use_procedural_hook) {
      $this->addProceduralHookComponent($components, $hook_info);
      return;
    }

    // If we're still here, make a class method hook.
    $hook_name = $hook_info['name'];
    // TODO: centralise this.
    $short_hook_name = preg_replace('@^hook_@', '', $hook_name);

    // Make the method name out of the short hook name in camel case.
    // TODO this is crap with e.g. hook_form_FORM_ID_alter becomes
    // formFORMIDAlter().
    $hook_method_name = CaseString::snake($short_hook_name)->camel();

    // Make the class method hook.
    $components[$hook_name . '_method'] = [
      // There are no specialised hook generators for class method hooks.
      'component_type' => 'HookImplementationClassMethod',
      'code_file' => $hook_info['destination'],
      'hook_name' => $hook_name,
      'hook_method_name' => $hook_method_name,
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
      // Add the procedural hook component.
      $this->addProceduralHookComponent($components, $hook_info);

      $components[$hook_name]['attribute'] = 'Drupal\Core\Hook\LegacyHook';

      $components[$hook_name]['function_docblock_lines'] = [
        'Legacy hook implementation.',
        '@todo Remove this method when support for Drupal core < 11.1 is dropped.',
      ];

      // Replace the hook body with a call to the Hooks class.
      $components[$hook_name]['body'] = [
        // Class extraction is not working, but that's probably a good thing
        // as it reduces future maintanance work to not have to remove an
        // import statement too!
        "\Drupal::service(\Drupal\%extension\Hooks\%PascalHooks::class)->{$hook_method_name}(...func_get_args());",
      ];
      $components[$hook_name]['body_indented'] = FALSE;

      // Explicitly declare the Hooks class as a service.
      // ARGH, can't use the 'Service' generator, as that will want to create a
      // class!
      $yaml_data = [
        'services' => [
          // Argh DRY class name!
          // TODO: move the class name to being created in this generator.
          'Drupal\%extension\Hooks\%PascalHooks' => [
            'class' => 'Drupal\%extension\Hooks\%PascalHooks',
            'autowire' => TRUE,
          ],
        ],
      ];
      $components['%module.services.yml'] = [
        'component_type' => 'YMLFile',
        // Probably have to use this deprecated token so the component merge
        // works?
        'filename' => '%module.services.yml',
        'yaml_data' => $yaml_data,
      ];
    }
  }

}
