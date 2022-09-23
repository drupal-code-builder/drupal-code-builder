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
    if (empty($this->containedComponents)) {
      return parent::buildComponentContents($children_contents);
    }

    $code = [];
    $code[] = '£items = array();';
    foreach ($this->containedComponents as $key => $child_item) {
      $code = array_merge($code, $child_item->getContents());
    }
    $code[] = '';
    $code[] = 'return £items;';

    $this->component_data->body = $code;
    $this->component_data->body_indented = FALSE;

    return parent::buildComponentContents($children_contents);
  }

}
