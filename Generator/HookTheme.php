<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for hook_theme() implementation.
 */
class HookTheme extends HookImplementation {

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // If we have no children, i.e. no ThemeHook components, then hand over to
    // the parent, which will output the default hook code.
    if (empty($children_contents)) {
      return parent::buildComponentContents($children_contents);
    }

    $code = [];
    $code[] = 'return [';
    foreach ($this->filterComponentContentsForRole($children_contents, 'item') as $menu_item_lines) {
      $code = array_merge($code, $menu_item_lines);
    }
    $code[] = '];';

    $this->component_data->body = $code;

    return parent::buildComponentContents($children_contents);
  }

}
