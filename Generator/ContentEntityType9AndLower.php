<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Attribute\DrupalCoreVersion;
use DrupalCodeBuilder\Attribute\RelatedBaseClass;

/**
 * Drupal 9 and earlier version of component.
 */
#[DrupalCoreVersion(9)]
#[DrupalCoreVersion(8)]
#[RelatedBaseClass('ContentEntityType')]
class ContentEntityType9AndLower extends ContentEntityType {

  /**
   * {@inheritdoc}
   */
  protected function addRevisionUiAnnotationData(&$annotation_data) {
    // Do nothing on versions before 10, which added the generic revision UI.
  }

}