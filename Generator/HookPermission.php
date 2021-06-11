<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for hook_permission() implementation.
 */
class HookPermission extends HookImplementation {

  public static function componentDataDefinition() {
    $definition = parent::componentDataDefinition();

    $definition['hook_name']->setLiteralDefault('hook_permission');

    return $definition;
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
    $this->component_data->has_wrapping_newlines = FALSE;

    $code = [];
    $code[] = '£permissions = array();';
    foreach ($this->filterComponentContentsForRole($children_contents, 'item') as $menu_item_lines) {
      $code = array_merge($code, $menu_item_lines);
    }
    $code[] = '';
    $code[] = 'return £permissions;';

    $this->component_data->body = $code;

    return parent::buildComponentContents($children_contents);
  }

}
