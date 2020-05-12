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

  /**
   * Sets the expression to acquire a value from the requesting component.
   *
   * Note there is no way to REMOVE acquired status, but this should be fine.
   *
   * @param string $expression
   *   An Expression Language expression. This should use object notation and
   *   does not need to bother with getItemValue(), since there is no need for
   *   JavaScript interpretation. Available variables:
   *    - requester: The requesting component.
   *
   * @return self
   *   Returns the definition, for chaining.
   */
  public function setAcquiringExpression(string $expression) :self {
    $this->acquiringExpression = $expression;

    $this->internal = TRUE;

    return $this;
  }

  public function getAcquiringExpression() :?string {
    return $this->acquiringExpression;
  }


}
