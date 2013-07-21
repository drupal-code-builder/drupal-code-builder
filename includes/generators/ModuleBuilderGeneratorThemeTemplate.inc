<?php

/**
 * Component generator: theme TPL file.
 */
class ModuleBuilderGeneratorThemeTemplate extends ModuleBuilderGeneratorComponent {

  /**
   * Return an array of subcomponent types.
   */
  protected function subComponents() {
    // We have no subcomponents. This override is here just for clarity.
    return array();
  }

  /**
   * Build the code files.
   */
  function collectFiles(&$files) {
    $theme_registry = theme_get_registry();
    // Our theme base was set in our incoming component data.
    $theme_info = $theme_registry[$this->component_data['theme_base']];

    //drush_print_r($this);
    //drush_print_r($theme_info);

    // Get the original TPL file we want to copy.
    // Due to how the theme registry works, this will be one of:
    //  - the original file from the module
    //  - an overridden tpl file in the current theme (eg, if you request
    //    node--article, and your theme has node.tpl, then you get that)
    //  - an overridden tpl file in a parent theme, same principle.
    $original_tpl_file = $theme_info['path'] . '/' . $theme_info['template'] . '.tpl.php';

    $tpl_code = file_get_contents($original_tpl_file);
    //print $tpl_code;

    $theme_path = path_to_theme();
    // Try a 'templates' folder inside it.
    if (file_exists($theme_path . '/templates')) {
      $file_path .= 'templates';
    }
    else {
      $file_path = '';
    }

    $files['info'] = array(
      'path' => $file_path,
      'filename' => $this->name . '.tpl.php',
      'body' => array(
        $tpl_code,
      ),
    );
  }

}
