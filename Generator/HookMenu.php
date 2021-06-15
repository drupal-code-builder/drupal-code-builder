<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for hook_menu() implementation.
 */
class HookMenu extends HookImplementation {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->getProperty('hook_name')->setLiteralDefault('hook_menu');

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // If we have no children, i.e. no RouterItem components, then hand over to
    // the parent, which will output the default hook code.
    if (empty($children_contents)) {
      return parent::buildComponentContents($children_contents);
    }

    $code = [];
    $code[] = '£items = array();';
    foreach ($this->filterComponentContentsForRole($children_contents, 'item') as $menu_item_lines) {
      $code = array_merge($code, $menu_item_lines);
    }
    $code[] = '';
    $code[] = 'return £items;';

    $this->component_data->body = $code;

    return parent::buildComponentContents($children_contents);
  }

}
