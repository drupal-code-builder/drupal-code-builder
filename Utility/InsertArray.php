<?php

namespace DrupalCodeBuilder\Utility;

// Verbatim copy of latest code from
// https://www.drupal.org/project/drupal/issues/66183

/**
 * Provides helpers to insert values into arrays.
 *
 * @ingroup utility
 */
class InsertArray {

  /**
   * Inserts values into an associative array before a given key.
   *
   * Values from $insert_array are inserted into $array before $key.
   *
   * @param array $array
   *   The array to insert into, passed by reference and altered in place.
   * @param mixed $key
   *   The key of $array to insert before.
   * @param array $insert_array
   *   An array whose keys and values should be inserted.
   *
   * @throws \Exception
   *   Throws an exception if the array does not have the key $key.
   */
  public static function insertBefore(&$array, $key, $insert_array) {
    static::insert($array, $key, $insert_array, TRUE);
  }

  /**
   * Inserts values into an associative array after a given key.
   *
   * Values from $insert_array are inserted into $array after $key.
   *
   * @param array $array
   *   The array to insert into, passed by reference and altered in place.
   * @param mixed $key
   *   The key of $array to insert after.
   * @param array $insert_array
   *   An array whose keys and values should be inserted.
   *
   * @throws \Exception
   *   Throws an exception if the array does not have the key $key.
   */
  public static function insertAfter(&$array, $key, $insert_array) {
    static::insert($array, $key, $insert_array, FALSE);
  }

  /**
   * Inserts values into an array before or after a given key.
   *
   * Helper for insertBefore() and insertAfter().
   *
   * Values from $insert_array are inserted into $array either before or after
   * $key.
   *
   * @param array $array
   *   The array to insert into, passed by reference and altered in place.
   * @param mixed $key
   *   The key of $array to insert before or after.
   * @param array $insert_array
   *   An array whose values should be inserted.
   * @param bool $before
   *   If TRUE, insert before the given key; if FALSE, insert after it.
   *
   * @throws \Exception
   *   Throws an exception if the array does not have the key $key.
   */
  protected static function insert(&$array, $key, $insert_array, $before) {
    if (!isset($array[$key])) {
      throw new \Exception("The array does not have the key $key.");
    }

    if ($before) {
      $offset = 0;
    }
    else {
      $offset = 1;
    }

    $pos = array_search($key, array_keys($array));
    $pos += $offset;

    $array_before = array_slice($array, 0, $pos);
    $array_after = array_slice($array, $pos);

    $array = array_merge(
      $array_before,
      $insert_array,
      $array_after
    );
  }

}
