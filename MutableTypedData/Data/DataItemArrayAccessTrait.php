<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

/**
 * Allows array access of complex data.
 */
trait DataItemArrayAccessTrait {

  public function offsetExists($offset) {
    return isset($this->value[$offset]);
  }

  public function offsetGet($offset) {
    // An array access on this is old code trying to work with array data, and
    // so we return the scalar value rather than the chid data item.
    return $this->value[$offset]->value;
  }

  public function offsetSet($offset, $value) {
    throw new \Exception();
  }

  public function offsetUnset($delta) {
    throw new \Exception();
  }

}