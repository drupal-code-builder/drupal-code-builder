<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use DrupalCodeBuilder\Exception\MergeDataLossException;

/**
 * Implements merge() for simple data.
 */
trait MergeableSimpleDataTrait {

  /**
   * {@inheritdoc}
   */
  public function merge(MergeableDataInterface $other): bool {
    // Different non-empty values would cause data loss.
    if (!$this->isEmpty() && !$other->isEmpty() && $this->value != $other->value) {
      throw new MergeDataLossException(sprintf(
        "Attempt to merge value '%s' into value '%s' at address %s.",
        (string) $this->value,
        (string) $other->value,
        $this->getAddress(),
      ));
    }

    // An empty value here can be overwritten by an incoming non-empty value.
    if ($this->isEmpty() && !$other->isEmpty()) {
      $this->value = $other->value;
      return TRUE;
    }

    return FALSE;
  }

}
