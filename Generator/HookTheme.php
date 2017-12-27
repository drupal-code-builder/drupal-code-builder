<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for hook_theme() implementation.
 */
class HookTheme extends HookImplementation {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   *
   * A HookImplementation generator should use as its name the full hook name,
   * e.g., 'hook_menu'.
   */
  public $name = 'hook_theme';

  /**
   * Constructor method; sets the component data.
   */
  function __construct($component_name, $component_data, $root_generator) {
    // Set some default properties.
    $component_data += array(
      'hook_name' => 'hook_theme',
    );

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
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

    $this->component_data['body_indent'] = 2;

    $this->component_data['body'] = $code;

    return parent::buildComponentContents($children_contents);
  }

}
