<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\PropertyListInterface;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Component generator: theme.
 *
 * Hierarchy of generators beneath this:
 *  - ?? theme_functions
 *    - template
 *    - tpl
 *  - info
 *  - readme
 */
class Theme extends RootComponent {

  /**
   * The sanity level this generator requires to operate.
   */
  public static $sanity_level = 'none';

  /**
   * The data for the component.
   *
   * This is only present on the base component (e.g., 'Theme'), so that the
   * data initially given by the user may be globally modified or added to by
   * components.
   *
   * This may contain the following properties:
   *   - 'theme_name': The machine name of the theme.
   *   - 'themeables': An array of theme hook names. These may include theme
   *      suggestions, separated with a '--'. For example, 'node' will output
   *      node.tpl.php, and 'node--page' will output node--page.tpl.php.
   *
   * Further properties the generating process will add:
   *   - 'theme_hook_bases': The base theme hook for each of the requested
   *      themeables. This is a lookup array keyed by the component names of
   *      the themeables.
   */
  public $component_data;

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition
      ->setLabel('Theme');

    $definition->getProperty('root_name')
      ->setLabel('Theme machine name')
      ->setLiteralDefault('my_theme');

    $definition->addProperties([
      'base' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault('theme')
        ->setRequired(TRUE),
      // TODO: move to RootComponent.
      'readable_name' => PropertyDefinition::create('string')
        ->setLabel('Theme readable name')
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("machineToLabel(get('..:root_name'))")
            ->setDependencies('..:root_name')
        ),
      'short_description' => PropertyDefinition::create('string')
        ->setLabel('Theme .info file description')
        ->setLiteralDefault('TODO: Description of theme')
        ->setRequired(TRUE),
    ]);
  }

  /**
   * Declares the subcomponents for this component.
   *
   * These are not necessarily child classes, just components this needs.
   *
   * @return
   *  An array of subcomponent types.
   */
  public function requiredComponents(): array {
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
