<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for hook_permission() implementation.
 */
class HookPermission extends HookImplementation {

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values.
   *   Valid properties are:
   *      - 'permissions': (optional) An array of permission names.
   */
  function __construct($component_data) {
    // Set some default properties.
    $component_data += array(
      'hook_name' => 'hook_permission',
    );

    parent::__construct($component_data);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // If we have no children, i.e. no Permission components, then hand over to
    // the parent, which will output the default hook code.
    if (empty($children_contents)) {
      return parent::buildComponentContents($children_contents);
    }

    // Code from child components comes as arrays of code lines, so no need to
    // trim it.
    $this->component_data['has_wrapping_newlines'] = FALSE;

    $code = array();
    $code[] = '£permissions = array();';
    foreach ($this->filterComponentContentsForRole($children_contents, 'item') as $menu_item_lines) {
      $code = array_merge($code, $menu_item_lines);
    }
    $code[] = '';
    $code[] = 'return £permissions;';

    $this->component_data['body'] = $code;

    return parent::buildComponentContents($children_contents);
  }

}
