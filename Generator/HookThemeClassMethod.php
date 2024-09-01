<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for hook_theme() implementation.
 *
 * Ugly as this duplicates HookTheme but I haven't the energy to figure out an
 * orthogonal system for hook implementation contents.
 */
class HookThemeClassMethod extends HookImplementationClassMethod {

  /**
   * {@inheritdoc}
   */
  protected function getFunctionBody(): array {
    // If we have no children, i.e. no ThemeHook components, then hand over to
    // the parent, which will output the default hook code.
    if ($this->containedComponents->isEmpty()) {
      return parent::getFunctionBody();
    }

    $code = [];
    $code[] = 'return [';
    foreach ($this->containedComponents['element'] as $key => $child_item) {
      $code = array_merge($code, $child_item->getContents());
    }
    $code[] = '];';

    $this->component_data->body_indented = FALSE;

    return $code;
  }

}
