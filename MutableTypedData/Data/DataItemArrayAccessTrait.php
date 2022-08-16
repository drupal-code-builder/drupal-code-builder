<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

/**
 * Allows array access of complex data.
 *
 * This is a shim to allow 3.x code that access component data as an array to
 * still work, so conversion can be done gradually.
 */
trait DataItemArrayAccessTrait {

  /**
   * {@inheritdoc}
   */
  public function offsetExists(mixed $offset): bool {
    return isset($this->value[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet(mixed $offset): mixed {
    if (!isset($this->properties[$offset])) {
      throw new \Exception(sprintf("No property %s at address %s. Valid properties are: %s.",
        $offset,
        $this->getAddress(),
        implode(', ', $this->getPropertyNames())
      ));
    }

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

  /**
   * {@inheritdoc}
   */
  public function offsetSet(mixed $offset, mixed $value): void {
    throw new \Exception(sprintf(
      "Attempt to set array key %s at %s.",
      $offset,
      $this->getAddress()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset(mixed $offset): void {
    throw new \Exception(sprintf(
      "Attempt to unset array key %s at %s.",
      $offset,
      $this->getAddress()
    ));
  }

}
