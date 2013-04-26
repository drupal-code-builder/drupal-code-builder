<?php

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
class ModuleBuilderGeneratorTheme extends ModuleBuilderGeneratorComponent {

  /**
   * Declares the subcomponents for this component.
   *
   * These are not necessarily child classes, just components this needs.
   *
   * @return
   *  An array of subcomponent types.
   */
  function subComponents() {
    $theme_data = $this->component_data;
    //drush_print_r($theme_data);

    drupal_theme_initialize();
    $theme_registry = theme_get_registry();

    $components = array();
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
        $components[$theme_hook_name] = array(
          'type' => 'themeTemplate',
          'component_data' => array(
            'theme_base' => $hook,
          ),
        );
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
