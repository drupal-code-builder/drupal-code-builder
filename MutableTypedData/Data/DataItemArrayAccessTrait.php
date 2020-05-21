<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

/**
 * Allows array access of complex data.
 *
 * This is a shim to allow 3.x code that access component data as an array to
 * still work, so conversion can be done gradually.
 */
trait DataItemArrayAccessTrait {

  public function offsetExists($offset) {
    return isset($this->value[$offset]);
  }

  public function offsetGet($offset) {
    // An array access on this is old code trying to work with array data, and
    // so we return the raw values rather than the chid data item.
    if ($this->properties[$offset]->getType() == 'complex' || $this->properties[$offset]->isMultiple()) {
        return $this->value[$offset]->export();
    }
    else {
      // Provide more detail if MTB throws an Exception.
      try {
        // We need to access the offset with get() because the magic __get()
        // won't get invoked here since we're in the same class.
        return $this->{$offset}->get();
      }
      catch (\Exception $e) {
        dump($this->export());
        dump($this->properties[$offset]->getType());
        dump($this->value[$offset]->export());
        throw $e;
      }
    }
  }

  public function offsetSet($offset, $value) {
    throw new \Exception("Attempt to set array key $offset.");
  }

  public function offsetUnset($offset) {
    throw new \Exception("Attempt to unset array key $offset.");
  }

}