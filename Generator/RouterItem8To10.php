<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Attribute\DrupalCoreVersion;
use DrupalCodeBuilder\Attribute\RelatedBaseClass;
use DrupalCodeBuilder\Definition\OptionDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\File\DrupalExtension;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;
use DrupalCodeBuilder\Generator\Render\PhpObject;
use DrupalCodeBuilder\Generator\Render\PhpValue;
use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\VariantDefinition;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\OptionsSortOrder;

/**
 * Generator for router item on Drupal 8, 9, 10.
 *
 * This is the same as Drupal 11 but removes the route attribute functionality.
 */
#[DrupalCoreVersion(10)]
#[DrupalCoreVersion(9)]
#[DrupalCoreVersion(8)]
#[RelatedBaseClass('RouterItem')]
class RouterItem8To10 extends RouterItem {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    // Hide the property rather than remove it, as the code that uses the
    // property would be too complicated to split up for removal.
    $definition
      ->getProperty('controller')
      ->getVariants()['controller']
      ->getProperty('use_route_attribute')
      ->setInternal(TRUE)
      ->setLiteralDefault(FALSE);
  }

}