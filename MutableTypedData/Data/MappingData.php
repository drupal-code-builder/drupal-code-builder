<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\DataItem;
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
  public function isEmpty() :bool {
    return is_empty($this->value);
  }

}
