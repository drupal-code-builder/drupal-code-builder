<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\PropertyDefinition as OriginalPropertyDefinition;

class PropertyDefinition extends OriginalPropertyDefinition {

  protected $internal = FALSE;

  // TODO: can this be done with defaults instead??
  protected $acquiringExpression = FALSE;

  public function setInternal(bool $internal) :self {
    $this->internal = $internal;

    return $this;
  }

  public function isInternal() :bool {
    return $this->internal;
  }

  // note there's no way to REMOVE acquired status, but this should be fine.
  // TODO!
  public function setAcquiringExpression(string $expression) :self {
    $this->acquiringExpression = $expression;

    $this->internal = TRUE;

    return $this;
  }

  public function getAcquiringExpression() :?string {
    return $this->acquiringExpression;
  }


}
