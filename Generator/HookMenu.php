<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\HookMenu.
 */

namespace ModuleBuider\Generator;

/**
 * Generator for hook_theme() implementation.
 */
class HookMenu extends HookImplementation {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   *
   * A HookImplementation generator should use as its name the full hook name,
   * e.g., 'hook_menu'.
   */
  public $name = 'hook_menu';

  /**
   * Called by ModuleCodeFile to collect functions from its child components.
   */
  public function componentFunctions() {
    // Get the function data from our parent class first.
    $return = parent::componentFunctions();

    // If we were requested without any menu items to make (ie via the Hooks
    // component rather than RouterItem), just return template code.
    if (empty($this->component_data['menu_items'])) {
      return $return;
    }

    // Otherwise, replace the template code with the menu items.
    // TODO: Lots here still to do -- just proof of concept!
    // TODO: WTF: code body seems to require a starting newline!?!? WTF!
    $code = "\n";
    $code .= "  \$items = array();\n";
    foreach ($this->component_data['menu_items'] as $menu_item_data) {
      $code .= "  \$items['" . $menu_item_data['path'] . "'] = array(\n";
      $code .= "    'title' => '" . $menu_item_data['title'] . "',\n";
      $code .= "  );\n";
    }
    
    $code .= "  return \$items;\n";

    $return[$this->name]['code'] = $code;

    return $return;
  }

}
