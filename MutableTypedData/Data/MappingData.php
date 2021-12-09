<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\DataItem;
use MutableTypedData\Exception\InvalidAccessException;
use MutableTypedData\Exception\InvalidInputException;
use DrupalCodeBuilder\Exception\MergeDataLossException;
use DrupalCodeBuilder\Utility\NestedArray;

/**
 * Represents arbitrarily-structured data.
 *
 * This is for array data where the structure is not known or varies, and so
 * defining it as a set of complex data properties is not possible.
 */
class MappingData extends DataItem implements MergeableDataInterface {

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

  /**
   * {@inheritdoc}
   */
  public function merge(MergeableDataInterface $other): bool {
    if ($this->value == $other->value) {
      return FALSE;
    }
    else {
      $data_added = FALSE;
      $this->value = $this->mergeArray($this->value, $other->value, $data_added);

      return $data_added;
    }
  }

  /**
   * Recursively merge arrays.
   *
   * This follows the same rules as data items:
   *  - incoming numeric keys with values not yet present are appended
   *  - incoming associative keys not yet present are added
   *  - incoming associative keys with mismatched values throw an exception
   *  - incoming associative keys with array values are recursively merged.
   *
   * @param array $this_data
   *   The current item's value.
   * @param array $other_data
   *   The incoming value.
   * @param bool &$data_added
   *   Indicates whether data has been added.
   *
   * @return array
   *   The resulting data.
   */
  protected function mergeArray(array $this_data, array $other_data, bool &$data_added): array {
    $new_data = $this_data;
    foreach ($other_data as $other_key => $other_value) {
      if (is_int($other_key)) {
        // For a numeric array, an incoming value not in the current data is
        // appended.
        if (array_search($other_value, $this_data) === FALSE) {
          $new_data[] = $other_value;
          $data_added = TRUE;
        }
      }
      else {
        if (isset($this_data[$other_key])) {
          if (is_array($other_value)) {
            // Array values in an associative array are recursively merged.
            $new_data[$other_key] = $this->mergeArray($this_data[$other_key], $other_value, $data_added);
          }
          else {
            // Scalar values at the same key in an associative array must match
            // because otherwise data would be lost.
            if ($this_data[$other_key] != $other_value) {
              throw new MergeDataLossException("Different data at key $other_key.");
            }
          }
        }
        else {
          // Keys in an associative array that don't exist currently are added.
          $new_data[$other_key] = $other_value;
          $data_added = TRUE;
        }
      }
    }
    return $new_data;
  }

}
