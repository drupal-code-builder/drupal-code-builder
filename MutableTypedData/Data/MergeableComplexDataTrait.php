<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use DrupalCodeBuilder\Exception\MergeDataLossException;

/**
 * Implements merge() for complex data.
 */
trait MergeableComplexDataTrait {

  /**
   * {@inheritdoc}
   */
  public function merge(MergeableDataInterface $other): bool {
    $result = [];

    $other_items = $other->items();
    foreach ($other_items as $property_name => $other_item) {
      $result[] = $this->{$property_name}->merge($other->{$property_name});
    }

    return (bool) array_sum($result);
  }

}
