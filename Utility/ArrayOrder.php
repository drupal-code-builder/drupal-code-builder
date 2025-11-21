<?php

namespace DrupalCodeBuilder\Utility;

/**
 * Provides helpers for setting the order of array items.
 *
 * @ingroup utility
 */
class ArrayOrder {

  /**
   * Moves an item to the front of an array, specifying by value.
   *
   * @param array &$array
   *   The array to change.
   * @param mixed $value
   *   The value to move.
   *
   * @throws \InvalidArgumentException
   *   Throws an exception if the value is not in the array.
   */
  public static function moveValueToFront(array &$array, mixed $value): void {
    $key = array_find($array, $value);

    if ($key === FALSE) {
      throw new \InvalidArgumentException('Value not found in array.');
    }

    static::moveKeyToFront($array, $key);
  }

  /**
   * Moves an item to the front of an array, specifying by key.
   *
   * @param array &$array
   *   The array to change.
   * @param mixed $key
   *   The key to move.
   *
   * @throws \InvalidArgumentException
   *   Throws an exception if the key is not in the array.
   */
  public static function moveKeyToFront(array &$array, mixed $key): void {
    if (!isset($array[$key])) {
      throw new \InvalidArgumentException('Key not found in array.');
    }

    $value = $array[$key];

    unset($array[$key]);

    InsertArray::insertBefore($array, array_key_first($array), [$key => $value]);
  }


  /**
   * Moves an item to the end of an array, specifying by value.
   *
   * @param array &$array
   *   The array to change.
   * @param mixed $value
   *   The value to move.
   *
   * @throws \InvalidArgumentException
   *   Throws an exception if the value is not in the array.
   */
  public static function moveValueToEnd(array &$array, mixed $value): void {
    $key = array_find($array, $value);

    if ($key === FALSE) {
      throw new \InvalidArgumentException('Value not found in array.');
    }

    unset($array[$key]);

    $array[$key] = $value;
  }

  /**
   * Moves an item to the end of an array, specifying by key.
   *
   * @param array &$array
   *   The array to change.
   * @param mixed $key
   *   The key to move.
   *
   * @throws \InvalidArgumentException
   *   Throws an exception if the key is not in the array.
   */
   public static function moveKeyToEnd(array &$array, mixed $key): void {
    if (!isset($array[$key])) {
      throw new \InvalidArgumentException('Key not found in array.');
    }

    $value = $array[$key];

    unset($array[$key]);

    $array[$key] = $value;
  }

}
