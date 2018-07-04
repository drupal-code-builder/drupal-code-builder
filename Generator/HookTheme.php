<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for hook_theme() implementation.
 */
class HookTheme extends HookImplementation {

  /**
   * Constructor method; sets the component data.
   */
  function __construct($component_data) {
    // Set some default properties.
    $component_data += array(
      'hook_name' => 'hook_theme',
    );

    parent::__construct($component_data);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // If we have no children, i.e. no theme hook components, then hand over to
    // the parent, which will output the default hook code.
    if (empty($children_contents)) {
      return parent::buildComponentContents($children_contents);
    }

    // Code from child components comes as arrays of code lines, so no need to
    // trim it.
    $this->component_data['has_wrapping_newlines'] = FALSE;

    $code = array();
    $code[] = 'return array(';
    foreach ($this->filterComponentContentsForRole($children_contents, 'item') as $menu_item_lines) {
      $code = array_merge($code, $menu_item_lines);
    }
    $code[] = ');';

    $this->component_data['body'] = $code;

    return parent::buildComponentContents($children_contents);
  }

}
