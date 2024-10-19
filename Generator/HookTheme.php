<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for hook_theme() implementation.
 */
class HookTheme extends HookImplementationProcedural {

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
