<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for hook_theme() implementation body lines.
 *
 * @see \DrupalCodeBuilder\Generator\HookImplementationBase
 */
class HookBodyHookTheme extends PHPFunctionBodyLines {

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    // If we have no children, i.e. no ThemeHook components, then return a
    // return of empty array.
    if ($this->containedComponents->isEmpty()) {
      return [
        'return [];',
      ];
    }

    $code = [];
    $code[] = 'return [';
    foreach ($this->containedComponents['element'] as $key => $child_item) {
      $code = array_merge($code, $child_item->getContents());
    }
    $code[] = '];';

    return $code;
  }

}
