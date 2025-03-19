<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\ArrayData;

class MergeableArrayData extends ArrayData implements MergeableDataInterface {

  /**
   * {@inheritdoc}
   */
  public function merge(MergeableDataInterface $other): bool {
    // Identical delta items are ignored, regardless of their delta index.
    // Incoming delta items which are not present here are appended.
    // Access $value directly to prevent defaults being set.
    // TODO: consider whether this is desirable or not!
    $other_values = $other->value;

    $other_deltas_to_merge = [];
    foreach ($other_values as $other_delta => $other_item) {
      foreach ($this->value as $delta => $item) {
        // Skip a delta item which is identical to one already present.
        if ($item->getRaw() == $other_item->getRaw()) {
          continue 2;
        }
      }

      $other_deltas_to_merge[] = $other_delta;
    }

    foreach ($other_deltas_to_merge as $delta) {
      $new_item = $other_values[$delta];

      $this->value[] = $new_item;

      // Update the parent, delta, name, and address of the added delta item.
      $new_item->parent = $this;
      $new_item->delta = array_key_last($this->value);
      $new_item->name = $new_item->delta;
      // We only need to zap the address; the next call to getAddress() will
      // rebuild it.
      $new_item->address = NULL;
    }

    return (bool) $other_deltas_to_merge;
  }

}
