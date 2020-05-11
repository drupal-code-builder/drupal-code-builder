<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\PropertyDefinition as OriginalPropertyDefinition;

class PropertyDefinition extends OriginalPropertyDefinition {

  protected $internal = FALSE;

  // TODO: can this be done with defaults instead??
  protected $acquired = FALSE;

  public function setInternal(bool $internal) :self {
    $this->internal = $internal;

    return $this;
  }

  public function isInternal() :bool {
    return $this->internal;
  }

  public function setAcquired(bool $acquired) :self {
    $this->acquired = $acquired;

    if ($acquired) {
      $this->internal = TRUE;
    }

    return $this;
  }

  public function isAcquired() :bool {
    return $this->acquired;
  }


}
