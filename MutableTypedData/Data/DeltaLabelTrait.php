<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

/**
 * Provides a method for data items for differentiated delta labels.
 */
trait DeltaLabelTrait {

  /**
   * Gets a label for this item which differentiates it from other deltas.
   *
   * @return string
   *   The label, with the delta if there is one and also a differentiating
   *   string if the underlying generator class provides one. This allows UIs
   *   to make it easier to distinguish between multiple delta items by showing
   *   more than just the index number.
   */
  public function getDifferentiatedLabel(): string {
    $label = $this->getLabel();

    // Just return the label if this isn't a delta, or if there's no associated
    // generator.
    if (!$this->isDelta() || empty($this->definition->generatorClass)) {
      return $label;
    }

    if ($qualifying_label = $this->definition->generatorClass::getDifferentiatedLabelSuffix($this)) {
      $label .= ' - ' . $qualifying_label;
    }

    return $label;
  }

}
