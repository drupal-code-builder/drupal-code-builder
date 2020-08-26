<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\DataItem;
use MutableTypedData\Exception\InvalidAccessException;
use MutableTypedData\Exception\InvalidInputException;

/**
 * Represents arbitrarily-structured data.
 *
 * This is for array data where the structure is not known or varies, and so
 * defining it as a set of complex data properties is not possible.
 */
class MappingData extends DataItem {

  protected $value = [];

  /**
   * {@inheritdoc}
   */
  public static function isSimple(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function items(): array {
    return [$this];
  }

  /**
   * {@inheritdoc}
   */
  public function validate(): array {
    return [];
  }


  public function __set($name, $value) {
    if ($name != 'value') {
      throw new InvalidAccessException(sprintf(
        "Only the 'value' property may be set on simple data at address %s.",
        $this->getAddress()
      ));
    }

    $this->set($value);
  }

  /**
   * {@inheritdoc}
   */
  public function set($value) {
    if (!is_array($value)) {
      throw new InvalidInputException(sprintf("Mapping value at %s must have an array set.",
        $this->getAddress()
      ));
    }

    $this->value = $value;
    $this->set = TRUE;

    parent::set($value);
  }

  /**
   * {@inheritdoc}
   */
  public function add($value) {
    if (!is_array($value)) {
      throw new InvalidInputException(sprintf(
        "Mapping value at %s must have an array set.",
        $this->getAddress()
      ));
    }

    foreach ($value as $item_key => $item_value) {
      $this->value[$item_key] = $item_value;
    }

    parent::set($value);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() :bool {
    return empty($this->value);
  }

}
