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
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values.
   *   Valid properties are:
   *      - 'menu_items': (optional) An array of menu items. Each item may
   *        contain the following properties, which are destined for the
   *        identically named hook_menu() item properties unless specified:
   *        - 'title': A string.
   *        - 'page callback': A string.
   *        - 'access arguments': A string which is the quoted array code.
   *          E.g. "array('access content')".
   *        - 'type': The quoted menu constant, e.g. 'MENU_SUGGESTED_ITEM'.
   */
  function __construct($component_name, $component_data = array()) {
    // Set some default properties.
    $component_data += array(
      'menu_items' => array(),
    );

    parent::__construct($component_name, $component_data);
  }

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
    $code = array();

    // Opening lines.
    // DX sugar: use £ for variables in the template code.
    $code[] = "£items = array();";
    $code[] = "";

    foreach ($this->component_data['menu_items'] as $menu_item_data) {
      // Add defaults for each menu item.
      $menu_item_data += array(
        // This doesn't need its own quotes, we put them in later.
        'title' => 'My Page',
        'page callback' => 'example_page',
        // These have to be a code string, not an actual array!
        'page arguments' => "array()",
        'access arguments' => "array('access content')",
      );

      $code[] = "£items['$menu_item_data[path]'] = array(";
      $code[] = "  'title' => '$menu_item_data[title]',";
      $code[] = "  'page callback' => '{$menu_item_data['page callback']}',";
      // This is an array, so not quoted.
      $code[] = "  'page arguments' => {$menu_item_data['page arguments']},";
      // This is an array, so not quoted.
      $code[] = "  'access arguments' => {$menu_item_data['access arguments']},";
      if (isset($menu_item_data['type'])) {
        // The type is a constant, so is not quoted.
        $code[] = "  'type' => $menu_item_data[type],";
      }
      $code[] = ");";
    }

    $code[] = "return £items;";

    $return[$this->name]['code'] = $code;

    // We return an array of lines, so we need newlines at start and finish.
    $return[$this->name]['has_wrapping_newlines'] = FALSE;

    return $return;
  }

}
