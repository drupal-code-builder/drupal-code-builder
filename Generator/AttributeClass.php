<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\DocBlock;
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
      [
        '\Attribute::TARGET_CLASS',
      ]
    );
  }

}
