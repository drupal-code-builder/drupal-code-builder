<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for hook_theme() implementation.
 */
// argh this needs to be BOTH procedural AND OO!
// TOO BAD to decouple hook CONTENTS from hook function/OO method.
// - use a trait!
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
