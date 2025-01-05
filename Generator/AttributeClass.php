<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\Render\PhpAttributes;

/**
 * Generator for PHP class files that define an attribute.
 */
class AttributeClass extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  protected function getClassAttributes(): ?PhpAttributes {
    return PhpAttributes::class(
      '\Attribute',
      '\Attribute::TARGET_CLASS',
    );
  }

}
