<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;


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
      // Hacky guard against using class property names as data property names.
      // See https://github.com/joachim-n/mutable-typed-data/issues/10.
      assert(is_object($this->{$property_name}));

      $result[] = $this->{$property_name}->merge($other->{$property_name});
    }

    return (bool) array_sum($result);
  }

}
