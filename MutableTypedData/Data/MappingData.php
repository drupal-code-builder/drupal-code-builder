<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\DataItem;
use MutableTypedData\Exception\InvalidInputException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

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
  public function validate() :ConstraintViolationListInterface {
    $violations = new ConstraintViolationList();
    // There are no possible violations.
    return $violations;
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

    parent::set($value);
  }

  public function __set($name, $value) {
    $this->set($value);
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
