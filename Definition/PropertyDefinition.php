<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\PropertyDefinition as OriginalPropertyDefinition;

class PropertyDefinition extends OriginalPropertyDefinition implements \ArrayAccess {

  protected $internal = FALSE;

  // TODO: can this be done with defaults instead??
  protected $acquiringExpression = FALSE;

  protected $presets = [];

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

  public function setPresets(array $presets) :self {
    $this->presets = $presets;

    return $this;
  }

  public function getPresets() :array {
    return $this->presets;
  }

  public function offsetExists($offset) {
    dump($this);
    throw new \Exception("Accessing definition $this->name as array");
  }

  public function offsetGet($offset) {
    throw new \Exception("Accessing definition $this->name as array");
  }

  public function offsetSet($offset, $value) {
    throw new \Exception("Accessing definition $this->name as array");
  }

  public function offsetUnset($offset){
    throw new \Exception("Accessing definition $this->name as array");
  }


}
